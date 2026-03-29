<?php

namespace Modules\Staff\Filament;

use Coolsam\Modules\Concerns\ModuleFilamentPlugin;
use Filament\Contracts\Plugin;
use Filament\Panel;

class StaffPlugin implements Plugin
{
    use ModuleFilamentPlugin;

    public function getModuleName(): string
    {
        return 'Staff';
    }

    public function getId(): string
    {
        return 'staff';
    }

    public function boot(Panel $panel): void
    {
        //
    }
}
