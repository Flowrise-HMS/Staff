<?php

namespace Modules\Staff\Filament\Clusters\Staff\Resources\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Modules\Staff\Filament\Clusters\Staff\Resources\StaffResource;

class ViewStaff extends ViewRecord
{
    protected static string $resource = StaffResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
