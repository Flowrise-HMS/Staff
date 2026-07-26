<?php

namespace Modules\Staff\Filament\Clusters\StaffCluster\Resources\Staff\Pages;

use Modules\Core\Filament\Pages\Concerns\RestrictsActivitiesToSuperAdmin;
use Modules\Staff\Filament\Clusters\StaffCluster\Resources\Staff\StaffResource;
use pxlrbt\FilamentActivityLog\Pages\ListActivitiesBySubject;

class ListStaffActivities extends ListActivitiesBySubject
{
    use RestrictsActivitiesToSuperAdmin;

    protected static string $resource = StaffResource::class;
}
