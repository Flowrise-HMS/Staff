<?php

namespace Modules\Staff\Filament\Clusters;

use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;

class StaffCluster extends Cluster
{
    protected static ?string $slug = 'staff-cluster';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static bool $shouldRegisterSubNavigation = false;
}
