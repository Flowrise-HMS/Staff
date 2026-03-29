<?php

namespace Modules\Staff\Filament\Clusters\Staff\Resources\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Modules\Staff\Filament\Clusters\Staff\Resources\StaffResource;

class ListStaff extends ListRecords
{
    protected static string $resource = StaffResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
