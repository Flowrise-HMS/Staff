<?php

namespace Modules\Staff\Filament\Clusters\StaffCluster\Resources\Staff\Pages;

use Modules\Staff\Filament\Clusters\StaffCluster\Resources\Staff\StaffResource;
use pxlrbt\FilamentActivityLog\Pages\ListActivitiesBySubject;

class ListStaffActivities extends ListActivitiesBySubject
{
    protected static string $resource = StaffResource::class;
}
