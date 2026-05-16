<?php

namespace Modules\Staff\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class StaffCustomPermissionSeeder extends Seeder
{
    /** @var array<string, string[]> permission name => web-guard roles */
    protected array $matrix = [
        'print_staff_id' => ['super_admin', 'operations_manager', 'department_head', 'it_admin'],
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ($this->matrix as $name => $roles) {
            $perm = Permission::query()->where(['name' => $name, 'guard_name' => 'web'])->first();
            if (! $perm) {
                continue;
            }

            foreach ($roles as $roleName) {
                Role::query()
                    ->where(['name' => $roleName, 'guard_name' => 'web'])
                    ->first()
                    ?->givePermissionTo($perm);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
