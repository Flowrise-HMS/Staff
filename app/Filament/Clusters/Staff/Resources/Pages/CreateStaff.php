<?php

namespace Modules\Staff\Filament\Clusters\Staff\Resources\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Staff\Filament\Clusters\Staff\Resources\StaffResource;

class CreateStaff extends CreateRecord
{
    protected static string $resource = StaffResource::class;
}
