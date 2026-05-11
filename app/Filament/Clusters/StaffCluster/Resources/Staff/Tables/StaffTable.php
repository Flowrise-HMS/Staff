<?php

namespace Modules\Staff\Filament\Clusters\StaffCluster\Resources\Staff\Tables;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Modules\Patient\Enums\Gender;
use Modules\Staff\Classes\Services\StaffAccountService;
use Modules\Staff\Enums\EmploymentStatus;
use Modules\Staff\Enums\StaffType;
use Ysfkaya\FilamentPhoneInput\Tables\PhoneColumn;

class StaffTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns(static::columns())
            ->filters(static::filters())
            ->recordActions(static::actions())
            ->toolbarActions(static::bulkActions())
            ->defaultSort('created_at', 'desc');
    }

    public static function columns(): array
    {
        return [
            TextColumn::make('#')->rowIndex(),
            TextColumn::make('staff_number')
                ->label('Staff #')
                ->searchable()
                ->sortable()
                ->weight('bold')
                ->copyable()
                ->tooltip(fn ($record) => $record->full_name),

            TextColumn::make('full_name')
                ->label('Name')
                ->searchable()
                ->sortable(),

            IconColumn::make('user_id')
                ->label('')
                ->boolean()
                ->trueIcon('heroicon-s-check-circle')
                ->trueColor('success')
                ->falseIcon('heroicon-m-x-circle')
                ->falseColor('gray')
                ->tooltip(fn ($record) => $record->user_id ? 'Has user account' : 'No user account'),

            TextColumn::make('gender')
                ->label('Gender')
                ->formatStateUsing(fn ($state) => $state?->getLabel())
                ->badge()
                ->toggleable(isToggledHiddenByDefault: true),

            TextColumn::make('staff_type')
                ->label('Type')
                ->badge()
                ->sortable(),

            TextColumn::make('employment_status')
                ->label('Status')
                ->badge()
                ->color(fn ($state) => $state->getColor())
                ->sortable(),

            TextColumn::make('departments.name')
                ->label('Department')
                ->listWithLineBreaks()
                ->limitList(1)
                ->tooltip(fn ($record) => $record->departments->pluck('name')->implode(', ')),

            TextColumn::make('contact.email')
                ->label('Email')
                ->getStateUsing(fn ($record) => $record->getEmail())
                ->searchable()
                ->sortable()
                ->toggleable(),

            PhoneColumn::make('contact.phone')
                ->label('Phone')
                ->getStateUsing(fn ($record) => $record->getPhone())
                ->toggleable(isToggledHiddenByDefault: true),

            TextColumn::make('hire_date')
                ->label('Hired')
                ->date('M d, Y')
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),

            TextColumn::make('tenure_years')
                ->label('Tenure')
                ->getStateUsing(fn ($record) => number_format($record->tenure_years, 1).' yrs')
                ->toggleable(isToggledHiddenByDefault: true),
        ];
    }

    public static function filters(): array
    {
        return [
            SelectFilter::make('staff_type')
                ->options(StaffType::class)
                ->label('Staff Type'),

            SelectFilter::make('employment_status')
                ->options(EmploymentStatus::class)
                ->label('Status'),

            SelectFilter::make('gender')
                ->options(Gender::class)
                ->label('Gender')
                ->preload(),

            SelectFilter::make('departments')
                ->relationship('departments', 'name')
                ->label('Department')
                ->preload()
                ->multiple(),

            Filter::make('without_account')
                ->label('Without User Account')
                ->query(fn ($query) => $query->whereNull('user_id')),

            Filter::make('with_account')
                ->label('With User Account')
                ->query(fn ($query) => $query->whereNotNull('user_id')),

            Filter::make('hire_date_range')
                ->label('Hire Date Range')
                ->schema([
                    DatePicker::make('hire_from'),
                    DatePicker::make('hire_to'),
                ])
                ->query(function ($query, array $data) {
                    return $query
                        ->when($data['hire_from'], fn ($q) => $q->whereDate('hire_date', '>=', $data['hire_from']))
                        ->when($data['hire_to'], fn ($q) => $q->whereDate('hire_date', '<=', $data['hire_to']));
                }),
        ];
    }

    public static function actions(): array
    {
        return [
            ActionGroup::make([
                ViewAction::make()
                    ->label('View'),
                Action::make('print_staff_id')
                    ->label(__('Staff ID Card'))
                    ->icon('heroicon-o-identification')
                    ->url(fn ($record) => route('staff.id-card', $record))
                    ->openUrlInNewTab()
                    ->visible(fn () => Auth::user()?->can('print_staff_id')),
                EditAction::make()
                    ->label('Edit'),
                DeleteAction::make()
                    ->label('Delete'),
                Action::make('createUserAccount')
                    ->label('Create User Account')
                    ->icon('heroicon-m-user-plus')
                    ->color('success')
                    ->visible(fn ($record) => ! $record->user_id)
                    ->schema([
                        TextInput::make('username')
                            ->nullable(),
                        TextInput::make('email')
                            ->email()
                            ->label('Email Address')
                            ->helperText('Leave empty to auto-generate based on name')
                            ->placeholder(fn ($record) => strtolower($record->first_name.'.'.$record->last_name).'@hospital.com'),

                        Toggle::make('send_credentials')
                            ->label('Send credentials via email')
                            ->default(true),
                    ])
                    ->action(function ($record, array $data) {
                        if (isset($data['username']) && ! empty($data['username'])) {
                            $user = User::where('username', $data['username'])->exists();
                            if ($user) {
                                throw ValidationException::withMessages(['username' => 'Username is already taken']);
                            }
                        }
                        $service = app(StaffAccountService::class);
                        $user = $service->createUserAccount($record, [
                            'username' => $data['username'] ?: null,
                            'email' => $data['email'] ?: null,
                            'send_credentials' => $data['send_credentials'],
                        ]);

                        if ($user) {
                            Notification::make()
                                ->title('User account created')
                                ->body('Login credentials have been '.($data['send_credentials'] ? 'sent to '.$user->email : 'created'))
                                ->success()
                                ->send();
                        }
                    }),

                Action::make('manageUserAccount')
                    ->label('Manage Account')
                    ->icon('heroicon-m-cog-6-tooth')
                    ->color('warning')
                    ->visible(fn ($record) => (bool) $record->user_id)
                    ->schema([
                        TextInput::make('username')
                            ->default(fn ($record) => $record?->user?->username ?? 'N/A')
                            ->disabled(),
                        TextInput::make('email')
                            ->email()
                            ->default(fn ($record) => $record?->user?->email)
                            ->label('Email Address')
                            ->disabled(),

                        Toggle::make('is_active')
                            ->label('Account Active')
                            ->default(fn ($record) => $record->user?->is_active),
                    ])
                    ->action(function ($record, array $data) {
                        $service = app(StaffAccountService::class);

                        if (isset($data['is_active'])) {
                            if ($data['is_active']) {
                                $service->activateAccount($record);
                            } else {
                                $service->deactivateAccount($record);
                            }
                        }

                        Notification::make()
                            ->title('Account updated')
                            ->success()
                            ->send();
                    }),

                Action::make('resetPassword')
                    ->label('Reset Password')
                    ->icon('heroicon-m-key')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => (bool) $record->user_id)
                    ->action(function ($record) {
                        $service = app(StaffAccountService::class);
                        $service->resetPassword($record);

                        Notification::make()
                            ->title('Password reset')
                            ->body('New credentials sent to '.$record->user->email)
                            ->success()
                            ->send();
                    }),

                Action::make('resendCredentials')
                    ->label('Resend Credentials')
                    ->icon('heroicon-m-envelope')
                    ->color('info')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => (bool) $record->user_id)
                    ->action(function ($record) {
                        $service = app(StaffAccountService::class);
                        $service->resendCredentials($record);

                        Notification::make()
                            ->title('Credentials resent')
                            ->body('Login credentials sent to '.$record->user->email)
                            ->success()
                            ->send();
                    }),

                Action::make('update_status')
                    ->label('Update Status')
                    ->icon('heroicon-o-pencil')
                    ->schema([
                        Select::make('status')
                            ->default(fn ($record) => $record?->employment_status)
                            ->options(EmploymentStatus::class)
                            ->searchable()
                            ->preload()
                            ->required(),
                    ])
                    ->action(function ($record, array $data) {
                        $record?->update(['employment_status' => $data['status']]);
                        Notification::make()
                            ->title('Staff status updated')
                            ->success()
                            ->send();
                    }),
            ]),
        ];
    }

    public static function bulkActions(): array
    {
        return [
            BulkActionGroup::make([
                DeleteBulkAction::make(),
            ]),
        ];
    }
}
