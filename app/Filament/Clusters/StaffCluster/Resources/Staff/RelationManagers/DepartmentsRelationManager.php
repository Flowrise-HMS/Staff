<?php

namespace Modules\Staff\Filament\Clusters\StaffCluster\Resources\Staff\RelationManagers;

use Filament\Actions\ActionGroup;
use Filament\Actions\AssociateAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DissociateBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Modules\Core\Models\Department;

class DepartmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'staffDepartments';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('department_id')
                    ->label('Department')
                    ->options(Department::query()->active()->pluck('name', 'id'))
                    ->required()
                    ->searchable()
                    ->preload(),

                TextInput::make('designation')
                    ->label('Designation/Title')
                    ->placeholder('e.g., Senior Nurse, Department Head')
                    ->maxLength(255),

                DatePicker::make('start_date')
                    ->label('Start Date')
                    ->default(now()),

                DatePicker::make('end_date')
                    ->label('End Date')
                    ->nullable(),

                Toggle::make('is_primary')
                    ->label('Primary Department')
                    ->default(false),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('department.name')
            ->columns([
                IconColumn::make('is_primary')
                    ->label('')
                    ->boolean()
                    ->trueIcon('heroicon-s-star')
                    ->trueColor('warning')
                    ->falseIcon('heroicon-m-outline-star')
                    ->falseColor('gray'),

                TextColumn::make('department.name')
                    ->label('Department')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('department.code')
                    ->label('Code')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('designation')
                    ->label('Designation')
                    ->placeholder('N/A'),

                TextColumn::make('start_date')
                    ->label('Started')
                    ->date()
                    ->sortable(),

                TextColumn::make('end_date')
                    ->label('Ended')
                    ->date()
                    ->sortable()
                    ->placeholder('Present'),

                IconColumn::make('is_active')
                    ->label('Status')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->trueIcon('heroicon-s-check')
                    ->falseIcon('heroicon-m-x-mark'),
            ])
            ->filters([
                SelectFilter::make('department')
                    ->relationship('department', 'name')
                    ->label('Department')
                    ->preload()
                    ->multiple(),
            ])
            ->headerActions([
                CreateAction::make(),
                AssociateAction::make()
                    ->label('Assign Existing'),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DissociateBulkAction::make(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
