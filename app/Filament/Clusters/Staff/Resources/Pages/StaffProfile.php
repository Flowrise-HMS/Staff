<?php

namespace Modules\Staff\Filament\Clusters\Staff\Resources\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Modules\Staff\Filament\Clusters\Staff\Resources\StaffResource;

class StaffProfile extends ViewRecord
{
    protected static string $resource = StaffResource::class;

    protected function getRedirectUrl(): string
    {
        return StaffResource::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back')
                ->label('Back to List')
                ->url(StaffResource::getUrl('index'))
                ->color('gray')
                ->icon('heroicon-o-arrow-left'),

            Actions\EditAction::make()
                ->label('Edit Staff'),
        ];
    }
}
