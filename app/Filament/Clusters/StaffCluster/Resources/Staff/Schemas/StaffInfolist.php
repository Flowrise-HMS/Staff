<?php

namespace Modules\Staff\Filament\Clusters\StaffCluster\Resources\Staff\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Modules\Staff\Enums\EmploymentStatus;

class StaffInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                static::headerSection(),
                static::personalInfoSection(),
                static::contactSection(),
                static::employmentSection(),
                static::addressSection(),
                static::emergencyContactSection(),
            ]);
    }

    public static function headerSection(): Section
    {
        return Section::make()
            ->columns(3)
            ->schema([
                TextEntry::make('staff_number')
                    ->label('Staff Number')
                    ->badge()
                    ->color('primary')
                    ->copyable()
                    ->columnSpan(1),

                TextEntry::make('staff_type')
                    ->label('Employment Type')
                    ->badge()
                    ->columnSpan(1),

                TextEntry::make('employment_status')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($state) => $state->getColor())
                    ->columnSpan(1),
            ]);
    }

    public static function personalInfoSection(): Section
    {
        return Section::make('Personal Information')
            ->icon('heroicon-o-user')
            ->columns([
                'sm' => 3,
                'md' => 6,
            ])
            ->schema([
                TextEntry::make('title')
                    ->label('Title')
                    ->columnSpan(1),

                TextEntry::make('first_name')
                    ->label('First Name')
                    ->columnSpan(1),

                TextEntry::make('middle_name')
                    ->label('Middle Name')
                    ->columnSpan(1),

                TextEntry::make('last_name')
                    ->label('Last Name')
                    ->columnSpan(1),

                TextEntry::make('gender')
                    ->label('Gender')
                    ->columnSpan(1),

                TextEntry::make('date_of_birth')
                    ->label('Date of Birth')
                    ->date('M d, Y')
                    ->columnSpan(1),
            ]);
    }

    public static function employmentSection(): Section
    {
        return Section::make('Employment')
            ->icon('heroicon-o-briefcase')
            ->columns([
                'sm' => 3,
                'md' => 6,
            ])
            ->schema([
                TextEntry::make('hire_date')
                    ->label('Hire Date')
                    ->date('M d, Y')
                    ->columnSpan(2),

                TextEntry::make('tenure_years')
                    ->label('Years of Service')
                    ->getStateUsing(fn ($record) => number_format($record->tenure_years, 1))
                    ->suffix(' years')
                    ->columnSpan(2),

                TextEntry::make('termination_date')
                    ->label('Termination Date')
                    ->date('M d, Y')
                    ->columnSpan(2),

                TextEntry::make('termination_reason')
                    ->label('Termination Reason')
                    ->columnSpanFull()
                    ->visible(fn ($record): bool => $record->employment_status === EmploymentStatus::TERMINATED),
            ]);
    }

    public static function contactSection(): Section
    {
        return Section::make('Contact')
            ->icon('heroicon-o-phone')
            ->collapsible()
            ->columns(2)
            ->schema([
                TextEntry::make('contact.email')
                    ->label('Email')
                    ->getStateUsing(fn ($record) => $record->getEmail())
                    ->copyable()
                    ->columnSpan(1),

                TextEntry::make('contact.phone')
                    ->label('Phone')
                    ->getStateUsing(fn ($record) => $record->getPhone())
                    ->copyable()
                    ->columnSpan(1),
            ]);
    }

    public static function addressSection(): Section
    {
        return Section::make('Address')
            ->icon('heroicon-o-map-pin')
            ->collapsible()
            ->collapsed()
            ->columns(1)
            ->schema([
                TextEntry::make('formatted_address')
                    ->label('Full Address')
                    ->getStateUsing(fn ($record) => $record->formatAddress("\n"))
                    ->columnSpanFull(),
            ]);
    }

    public static function emergencyContactSection(): Section
    {
        return Section::make('Emergency Contact')
            ->icon('heroicon-o-exclamation-circle')
            ->collapsible()
            ->collapsed()
            ->columns([
                'sm' => 2,
                'md' => 3,
            ])
            ->schema([
                TextEntry::make('emergency_contact.name')
                    ->label('Contact Name')
                    ->columnSpan(1),

                TextEntry::make('emergency_contact.phone')
                    ->label('Contact Phone')
                    ->copyable()
                    ->columnSpan(1),

                TextEntry::make('emergency_contact.relationship')
                    ->label('Relationship')
                    ->columnSpan(1),
            ]);
    }
}
