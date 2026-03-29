<?php

namespace Modules\Staff\Filament\Clusters\StaffCluster\Resources\Staff;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Modules\Staff\Classes\Services\StaffSearchService;
use Modules\Staff\Filament\Clusters\StaffCluster;
use Modules\Staff\Filament\Clusters\StaffCluster\Resources\Staff\Pages\CreateStaff;
use Modules\Staff\Filament\Clusters\StaffCluster\Resources\Staff\Pages\EditStaff;
use Modules\Staff\Filament\Clusters\StaffCluster\Resources\Staff\Pages\ListStaff;
use Modules\Staff\Filament\Clusters\StaffCluster\Resources\Staff\Pages\ViewStaff;
use Modules\Staff\Filament\Clusters\StaffCluster\Resources\Staff\RelationManagers\CredentialsRelationManager;
use Modules\Staff\Filament\Clusters\StaffCluster\Resources\Staff\RelationManagers\DepartmentsRelationManager;
use Modules\Staff\Filament\Clusters\StaffCluster\Resources\Staff\RelationManagers\SpecialtiesRelationManager;
use Modules\Staff\Filament\Clusters\StaffCluster\Resources\Staff\Schemas\StaffForm;
use Modules\Staff\Filament\Clusters\StaffCluster\Resources\Staff\Schemas\StaffInfolist;
use Modules\Staff\Filament\Clusters\StaffCluster\Resources\Staff\Tables\StaffTable;
use Modules\Staff\Models\Staff;

class StaffResource extends Resource
{
    protected static ?string $model = Staff::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?string $cluster = StaffCluster::class;

    protected static ?string $recordTitleAttribute = 'full_name';

    public static function getGloballySearchableAttributes(): array
    {
        return app(StaffSearchService::class)->getSearchableFields();
    }

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

    public static function getRelations(): array
    {
        return [
            CredentialsRelationManager::class,
            DepartmentsRelationManager::class,
            SpecialtiesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStaff::route('/'),
            'create' => CreateStaff::route('/create'),
            'view' => ViewStaff::route('/{record}'),
            'edit' => EditStaff::route('/{record}/edit'),
        ];
    }
}
