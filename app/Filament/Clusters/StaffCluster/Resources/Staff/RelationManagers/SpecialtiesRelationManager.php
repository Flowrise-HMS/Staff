<?php

namespace Modules\Staff\Filament\Clusters\StaffCluster\Resources\Staff\RelationManagers;

use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SpecialtiesRelationManager extends RelationManager
{
    protected static string $relationship = 'specialties';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('specialty_name')
                    ->label('Specialty Name')
                    ->required()
                    ->placeholder('e.g., Internal Medicine')
                    ->maxLength(255),

                TextInput::make('specialty_code')
                    ->label('Specialty Code')
                    ->placeholder('e.g., INT-MED')
                    ->maxLength(50),

                TextInput::make('description')
                    ->label('Description')
                    ->placeholder('Brief description of the specialty')
                    ->maxLength(500),

                TextInput::make('issuing_body')
                    ->label('Issuing Body')
                    ->placeholder('e.g., Ghana Medical and Dental Council')
                    ->maxLength(255),

                TextInput::make('certificate_number')
                    ->label('Certificate Number')
                    ->placeholder('License/Certificate number')
                    ->maxLength(100),

                DatePicker::make('certification_date')
                    ->label('Certification Date'),

                DatePicker::make('expiry_date')
                    ->label('Expiry Date')
                    ->nullable(),

                Toggle::make('is_primary')
                    ->label('Primary Specialty')
                    ->default(false),

                FileUpload::make('certificate_path')
                    ->label('Certificate Document')
                    ->directory('staff/specialties')
                    ->acceptedFileTypes(['application/pdf', 'image/*'])
                    ->maxSize(10240),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('specialty_name')
            ->columns([
                IconColumn::make('is_primary')
                    ->label('')
                    ->boolean()
                    ->trueIcon('heroicon-s-star')
                    ->trueColor('warning')
                    ->falseIcon('heroicon-m-outline-star')
                    ->falseColor('gray'),

                TextColumn::make('specialty_name')
                    ->label('Specialty')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('specialty_code')
                    ->label('Code')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('issuing_body')
                    ->label('Issuing Body')
                    ->limit(30),

                TextColumn::make('certificate_number')
                    ->label('Certificate #')
                    ->copyable(),

                TextColumn::make('certification_date')
                    ->label('Certified')
                    ->date()
                    ->sortable(),

                TextColumn::make('expiry_date')
                    ->label('Expires')
                    ->date()
                    ->sortable()
                    ->color(fn ($record) => $record->is_expiring_soon ? 'warning' : ($record->is_expired ? 'danger' : 'gray')),

                IconColumn::make('is_expired')
                    ->label('Status')
                    ->boolean()
                    ->trueColor('danger')
                    ->falseColor('success')
                    ->trueIcon('heroicon-s-x-circle')
                    ->falseIcon('heroicon-s-check-circle'),
            ])
            ->filters([
                SelectFilter::make('specialty_name')
                    ->label('Specialty')
                    ->preload()
                    ->multiple(),

                Filter::make('expired')
                    ->label('Expired')
                    ->query(fn (Builder $query) => $query->expired()),

                Filter::make('expiring_soon')
                    ->label('Expiring Soon')
                    ->query(fn (Builder $query) => $query->expiringSoon()),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
