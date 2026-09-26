<?php

namespace Modules\Staff\Filament\Clusters\StaffCluster\Resources\Staff\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Modules\Staff\Classes\Services\StaffAccountService;
use Modules\Staff\Filament\Clusters\StaffCluster\Resources\Staff\Schemas\StaffAccountFields;
use Modules\Staff\Filament\Clusters\StaffCluster\Resources\Staff\StaffResource;
use Modules\Staff\Models\Staff;

class CreateStaff extends CreateRecord
{
    protected static string $resource = StaffResource::class;

    /**
     * Saves the staff record and, when login access is switched on, its user account
     * in one transaction so a failed account never leaves a half-created staff member.
     *
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        $account = Arr::pull($data, 'account', []);

        return DB::transaction(function () use ($data, $account): Model {
            /** @var Staff $staff */
            $staff = parent::handleRecordCreation($data);

            if (($account['login_access'] ?? false) && StaffAccountFields::canManageAccounts()) {
                app(StaffAccountService::class)->syncAccount($staff, $account);
            }

            return $staff;
        });
    }
}
