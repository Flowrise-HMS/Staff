<?php

namespace Modules\Staff\Filament\Clusters\Staff\Resources\Pages;

use Filament\Resources\Pages\EditRecord;
use Modules\Staff\Filament\Clusters\Staff\Resources\StaffResource;

class EditStaff extends EditRecord
{
    protected static string $resource = StaffResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('view', ['record' => $this->record]);
    }
}
