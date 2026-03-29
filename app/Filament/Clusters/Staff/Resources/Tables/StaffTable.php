<?php

namespace Modules\Staff\Filament\Clusters\Staff\Resources\Tables;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Modules\Patient\Enums\Gender;
use Modules\Staff\Enums\EmploymentStatus;
use Modules\Staff\Enums\StaffType;
use Modules\Staff\Filament\Clusters\Staff\Resources\Pages\StaffProfile;
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
                EditAction::make()
                    ->label('Edit'),
                DeleteAction::make()
                    ->label('Delete'),
                Action::make('update_status')
                    ->requiresConfirmation()
                    ->icon('heroicon-o-pencil')
                    ->schema([
                        Select::make('status')->options(EmploymentStatus::class)
                            ->searchable()
                            ->preload()
                            ->required(),
                    ])
                    ->action(function ($record, array $data) {
                        $record?->update(['status' => $data['status']]);
                        Notification::make()->title('Staff Status has been updated')
                            ->success()
                            ->send();
                    }),
                Action::make('view_profile')
                    ->label('View Profile')
                    ->icon('heroicon-m-user-circle')
                    ->url(fn ($record) => StaffProfile::getUrl(['record' => $record])),
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
