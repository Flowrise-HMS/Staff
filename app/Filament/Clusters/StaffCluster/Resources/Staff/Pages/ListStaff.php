<?php

namespace Modules\Staff\Filament\Clusters\StaffCluster\Resources\Staff\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\Core\Filament\Support\SuperAdminExportAction;
use Modules\Staff\Filament\Clusters\StaffCluster\Resources\Staff\StaffResource;
use Modules\Staff\Filament\Exports\StaffExporter;

class ListStaff extends ListRecords
{
    protected static string $resource = StaffResource::class;

    protected function getHeaderActions(): array
    {
        return [
            SuperAdminExportAction::make(StaffExporter::class),
            CreateAction::make(),
        ];
    }
}
