<?php

namespace Modules\Staff\Filament\Clusters\StaffCluster\Resources\Staff\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Context;
use Modules\Core\Classes\Services\BranchService;
use Modules\Core\Enums\Title;
use Modules\Core\Models\Branch;
use Modules\Patient\Enums\Gender;
use Modules\Patient\Enums\RelationshipType;
use Modules\Staff\Enums\EmploymentStatus;
use Modules\Staff\Enums\StaffType;
use Nnjeim\World\Models\Country;
use Nnjeim\World\Models\State;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;

class StaffForm
{
    public static function configure(Schema $schema): Schema
    {
        $personalInfo = static::personalInfoSection();
        $contactInfo = static::contactSection();
        $addressInfo = static::addressSection();
        $employmentInfo = static::employmentSection();
        $emergencyInfo = static::emergencyContactSection();

        return $schema
            ->components([
                Wizard::make([
                    Step::make($personalInfo->getHeading())->schema([
                        $personalInfo,
                        $employmentInfo,
                    ]),
                    Step::make($contactInfo->getHeading())->schema([
                        $contactInfo, $addressInfo,
                    ]),
                    Step::make($emergencyInfo->getHeading())->schema([$emergencyInfo]),
                ])
                    ->columnSpanFull()
                    ->skippable(),
            ]);
    }

    public static function personalInfoSection(): Section
    {
        return Section::make('Personal Information')
            ->icon('heroicon-o-user')
            ->columns(2)
            ->schema([
                Select::make('branch_id')
                    ->relationship('branch', 'name')
                    ->preload()
                    ->searchable()
                    ->default(app(BranchService::class)->getDefaultBranchId())
                    ->label('Branch'),
                Select::make('title')
                    ->options(Title::class)
                    ->searchable(),

                Group::make([
                    TextInput::make('first_name')
                        ->required()
                        ->placeholder('First name')
                        ->maxLength(100),

                    TextInput::make('middle_name')
                        ->placeholder('Middle name')
                        ->maxLength(100),

                    TextInput::make('last_name')
                        ->required()
                        ->placeholder('Last name')
                        ->maxLength(100),
                ])
                    ->columnSpanFull()
                    ->columns(3),
                Select::make('gender')
                    ->options(Gender::class)
                    ->required()
                    ->placeholder('Select gender'),

                DatePicker::make('date_of_birth')
                    ->placeholder('Date of birth')
                    ->maxDate(now()->subYears(18)),
            ]);
    }

    public static function contactSection(): Section
    {
        return Section::make('Contact Information')
            ->icon('heroicon-o-phone')
            ->collapsible()
            ->columns(2)
            ->schema([
                PhoneInput::make('contact.phone')
                    ->label('Phone Number')
                    ->defaultCountry(config('core.default_country_code', 'GH')),

                TextInput::make('contact.email')
                    ->label('Email Address')
                    ->email()
                    ->placeholder('name@example.com')
                    ->prefixIcon('heroicon-m-envelope'),
            ]);
    }

    public static function addressSection(): Section
    {
        return Section::make('Address')
            ->icon('heroicon-o-map-pin')
            ->collapsible()
            ->columns(2)
            ->schema([
                TextInput::make('address.street')
                    ->label('Street Address')
                    ->placeholder('House number, street name')
                    ->columnSpanFull(),
                Grid::make()
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('address.city')
                            ->label('City')
                            ->placeholder('Accra'),
                        TextInput::make('address.postal_code')
                            ->label('Postal Code')
                            ->placeholder('GM-XXX-XXXX')
                            ->maxLength(20),
                        Select::make('address.country')
                            ->label('Country')
                            ->default(config('core.default_country_code', 'GH'))
                            ->options(Country::pluck('name', 'iso2')?->toArray() ?? [])
                            ->live()
                            ->preload()
                            ->searchable(),
                        Select::make('address.region')
                            ->label('Region')
                            ->options(fn (Get $get) => State::where('country_code', $get('address.country') ?? config('core.default_country_code', 'GH'))
                                ->pluck('name')->toArray())
                            ->searchable(),

                    ]),
            ]);
    }

    public static function employmentSection(): Section
    {
        return Section::make('Employment Details')
            ->icon('heroicon-o-briefcase')
            ->columns(3)
            ->schema([
                Select::make('staff_type')
                    ->options(StaffType::class)
                    ->required()
                    ->default(StaffType::default())
                    ->searchable(),

                Select::make('employment_status')
                    ->options(EmploymentStatus::class)
                    ->required()
                    ->default(EmploymentStatus::default())
                    ->searchable(),

                DatePicker::make('hire_date')
                    ->default(now())
                    ->label('Hire Date'),
            ]);
    }

    public static function emergencyContactSection(): Section
    {
        return Section::make('Emergency Contact')
            ->icon('heroicon-o-exclamation-circle')
            ->description('Who should we contact in case of emergency?')
            ->collapsible()
            ->columns(3)
            ->schema([
                TextInput::make('emergency_contact.name')
                    ->label('Contact Name')
                    ->placeholder('Full name')
                    ->maxLength(200),
                PhoneInput::make('emergency_contact.phone')
                    ->label('Contact Phone'),
                Select::make('emergency_contact.relationship')
                    ->label('Relationship')
                    ->placeholder('Select relationship')
                    ->options(RelationshipType::class),
            ]);
    }
}
