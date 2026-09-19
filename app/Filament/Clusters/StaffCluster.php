<?php

namespace Modules\Staff\Filament\Clusters;

use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;
use Modules\Core\Enums\SidebarGroup;

class StaffCluster extends Cluster
{
    protected static ?string $slug = 'staff-cluster';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|\UnitEnum|null $navigationGroup = SidebarGroup::Operations;

    protected static ?int $navigationSort = 20;

    protected static bool $shouldRegisterSubNavigation = false;
}
