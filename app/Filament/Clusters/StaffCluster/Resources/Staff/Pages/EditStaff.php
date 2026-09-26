<?php

namespace Modules\Staff\Filament\Clusters\StaffCluster\Resources\Staff\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Modules\Staff\Classes\Services\StaffAccountService;
use Modules\Staff\Filament\Clusters\StaffCluster\Resources\Staff\Actions\StaffAccountActions;
use Modules\Staff\Filament\Clusters\StaffCluster\Resources\Staff\Schemas\StaffAccountFields;
use Modules\Staff\Filament\Clusters\StaffCluster\Resources\Staff\StaffResource;
use Modules\Staff\Models\Staff;

class EditStaff extends EditRecord
{
    protected static string $resource = StaffResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('view', ['record' => $this->record]);
    }

    protected function getHeaderActions(): array
    {
        return [
            StaffAccountActions::impersonate(),
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var Staff $staff */
        $staff = $this->getRecord();
        $data['account'] = StaffAccountFields::stateFor($staff);

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $account = Arr::pull($data, 'account');

        return DB::transaction(function () use ($record, $data, $account): Model {
            /** @var Staff $staff */
            $staff = parent::handleRecordUpdate($record, $data);

            if (is_array($account) && StaffAccountFields::canManageAccounts($staff)) {
                app(StaffAccountService::class)->syncAccount($staff->refresh(), $account);
            }

            return $staff;
        });
    }
}
