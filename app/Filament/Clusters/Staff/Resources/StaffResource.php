<?php

namespace Modules\Staff\Filament\Clusters\Staff\Resources;

use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Modules\Staff\Filament\Clusters\Staff\Resources\Pages\CreateStaff;
use Modules\Staff\Filament\Clusters\Staff\Resources\Pages\EditStaff;
use Modules\Staff\Filament\Clusters\Staff\Resources\Pages\ListStaff;
use Modules\Staff\Filament\Clusters\Staff\Resources\Pages\StaffProfile;
use Modules\Staff\Filament\Clusters\Staff\Resources\Pages\ViewStaff;
use Modules\Staff\Filament\Clusters\Staff\Resources\Schemas\StaffForm;
use Modules\Staff\Filament\Clusters\Staff\Resources\Schemas\StaffInfolist;
use Modules\Staff\Filament\Clusters\Staff\Resources\Tables\StaffTable;
use Modules\Staff\Filament\Clusters\StaffCluster;
use Modules\Staff\Models\Staff;

class StaffResource extends Resource
{
    protected static ?string $model = Staff::class;

    protected static ?string $cluster = StaffCluster::class;

    protected static ?string $recordTitleAttribute = 'full_name';

    protected static ?string $slug = 'staff';

    public static function form(Schema $schema): Schema
    {
        return StaffForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return StaffInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return StaffTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStaff::route('/'),
            'create' => CreateStaff::route('/create'),
            'view' => ViewStaff::route('/{record}'),
            'edit' => EditStaff::route('/{record}/edit'),
            'profile' => StaffProfile::route('/{record}/profile'),
        ];
    }
}
