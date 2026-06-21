<?php

namespace Modules\Staff\Filament\Clusters\StaffCluster\Resources\Staff\Pages;

use Modules\Staff\Filament\Clusters\StaffCluster\Resources\Staff\StaffResource;
use pxlrbt\FilamentActivityLog\Pages\ListActivities;

class ListStaffActivities extends ListActivities
{
    protected static string $resource = StaffResource::class;
}
