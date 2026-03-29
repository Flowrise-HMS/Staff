<?php

namespace Modules\Staff\Filament\Clusters\StaffCluster\Resources\Staff\RelationManagers;

use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DissociateBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Modules\Staff\Enums\CredentialStatus;
use Modules\Staff\Enums\CredentialType;
use Nnjeim\World\Models\Country;

class CredentialsRelationManager extends RelationManager
{
    protected static string $relationship = 'credentials';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('credential_type')
                    ->options(CredentialType::class)
                    ->required()
                    ->searchable(),

                TextInput::make('credential_number')
                    ->required()
                    ->maxLength(255),

                TextInput::make('issuing_authority')
                    ->placeholder('Ghana Medical and Dental Council')
                    ->label('Issuing Authority'),

                Select::make('issuing_country')
                    ->label('Issuing Country')
                    ->default(config('core.default_country_code', 'GH'))
                    ->options(Country::pluck('name', 'iso2')?->toArray() ?? [])
                    ->searchable()
                    ->preload(),

                TextInput::make('issuing_state')
                    ->label('Issuing State/Region')
                    ->maxLength(100),

                DatePicker::make('issue_date')
                    ->label('Issue Date'),

                DatePicker::make('expiry_date')
                    ->label('Expiry Date'),

                Select::make('status')
                    ->options(CredentialStatus::class)
                    ->default(CredentialStatus::PENDING)
                    ->required(),

                TextInput::make('verification_notes')
                    ->label('Verification Notes')
                    ->maxLength(500),

                FileUpload::make('document_path')
                    ->label('Document')
                    ->directory('staff/credentials')
                    ->acceptedFileTypes(['application/pdf', 'image/*'])
                    ->maxSize(10240),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('credential_number')
            ->columns([
                TextColumn::make('credential_type')
                    ->label('Type')
                    ->badge()
                    ->sortable(),

                TextColumn::make('credential_number')
                    ->label('Credential #')
                    ->searchable()
                    ->copyable(),

                TextColumn::make('issuing_authority')
                    ->label('Issuing Authority')
                    ->limit(30),

                TextColumn::make('issue_date')
                    ->label('Issued')
                    ->date()
                    ->sortable(),

                TextColumn::make('expiry_date')
                    ->label('Expires')
                    ->date()
                    ->sortable()
                    ->color(fn ($record) => $record->is_expiring_soon ? 'warning' : ($record->is_expired ? 'danger' : 'gray')),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge(),

                TextColumn::make('verifiedBy.name')
                    ->label('Verified By')
                    ->default('N/A')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('credential_type')
                    ->options(CredentialType::class)
                    ->label('Credential Type'),

                SelectFilter::make('status')
                    ->options(CredentialStatus::class)
                    ->label('Status'),
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
                    DissociateBulkAction::make(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
