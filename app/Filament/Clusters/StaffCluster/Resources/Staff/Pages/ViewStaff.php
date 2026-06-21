<?php

namespace Modules\Staff\Filament\Clusters\StaffCluster\Resources\Staff\Pages;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Modules\Staff\Filament\Clusters\StaffCluster\Resources\Staff\StaffResource;

class ViewStaff extends ViewRecord
{
    protected static string $resource = StaffResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('activities')
                ->label('Activities')
                ->icon('heroicon-o-bell-alert')
                ->url(fn () => StaffResource::getUrl('activities', ['record' => $this->getRecord()])),
            Action::make('print_staff_id')
                ->label(__('Staff ID card'))
                ->icon(Heroicon::OutlinedIdentification)
                ->url(fn () => route('staff.id-card', $this->getRecord()))
                ->openUrlInNewTab()
                ->visible(fn () => Auth::user()?->can('print_staff_id')),
            EditAction::make(),
            DeleteAction::make(),
        ];
    }
}
