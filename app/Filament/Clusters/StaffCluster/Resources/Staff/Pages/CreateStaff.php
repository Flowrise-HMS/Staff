<?php

namespace Modules\Staff\Filament\Clusters\StaffCluster\Resources\Staff\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Staff\Filament\Clusters\StaffCluster\Resources\Staff\StaffResource;

class CreateStaff extends CreateRecord
{
    protected static string $resource = StaffResource::class;
}
