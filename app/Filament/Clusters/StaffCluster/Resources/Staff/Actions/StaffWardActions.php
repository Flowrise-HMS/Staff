<?php

namespace Modules\Staff\Filament\Clusters\StaffCluster\Resources\Staff\Actions;

use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Modules\Core\Classes\Services\WardStaffAssignmentService;
use Modules\Core\Enums\WardStaffRole;
use Modules\Core\Models\Location;
use Modules\Staff\Models\Staff;

/**
 * Ward assignment for a staff record, shared by the Staff table and the View staff page.
 * Ward assignments are held against the staff member's user account, so the
 * action is only offered once they have one.
 */
class StaffWardActions
{
    public static function assignToWard(): Action
    {
        return Action::make('assignToWard')
            ->label(__('Assign to Ward'))
            ->icon('heroicon-m-building-office-2')
            ->color('primary')
            ->modalHeading(fn (Staff $record): string => __('Assign :name to a ward', ['name' => $record->full_name]))
            ->visible(fn (Staff $record): bool => (bool) $record->user_id
                && (bool) Auth::user()?->can('Update Location'))
            ->schema([
                Select::make('location_id')
                    ->label(__('Ward'))
                    ->options(fn (): array => app(WardStaffAssignmentService::class)->wardOptions())
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('assignment')
                    ->label(__('Assign as'))
                    ->options(WardStaffAssignmentService::assignmentOptions())
                    ->default(WardStaffRole::WARD_NURSE->value)
                    ->required(),
            ])
            ->action(function (Staff $record, array $data): void {
                $ward = Location::query()->findOrFail($data['location_id']);

                app(WardStaffAssignmentService::class)->assign(
                    $ward,
                    $record->user_id,
                    $data['assignment'],
                    Auth::id(),
                );

                Notification::make()
                    ->title(__(':name assigned to :ward', ['name' => $record->full_name, 'ward' => $ward->name]))
                    ->success()
                    ->send();
            });
    }
}
