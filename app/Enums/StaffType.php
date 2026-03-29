<?php

namespace Modules\Staff\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum StaffType: string implements HasColor, HasDescription, HasLabel
{
    case FULL_TIME = 'full_time';
    case PART_TIME = 'part_time';
    case CONTRACT = 'contract';
    case VOLUNTEER = 'volunteer';
    case INTERN = 'intern';
    case RESIDENT = 'resident';
    case CONSULTANT = 'consultant';

    public function getLabel(): string|Htmlable|null
    {
        return match ($this) {
            self::FULL_TIME => 'Full Time',
            self::PART_TIME => 'Part Time',
            self::CONTRACT => 'Contract',
            self::VOLUNTEER => 'Volunteer',
            self::INTERN => 'Intern',
            self::RESIDENT => 'Resident',
            self::CONSULTANT => 'Consultant',
        };
    }

    public function getDescription(): ?string
    {
        return match ($this) {
            self::FULL_TIME => 'Regular full-time employee with benefits',
            self::PART_TIME => 'Employee working fewer than standard hours',
            self::CONTRACT => 'Fixed-term contract employee',
            self::VOLUNTEER => 'Unpaid volunteer staff member',
            self::INTERN => 'Student or trainee in internship program',
            self::RESIDENT => 'Medical/resident doctor in training',
            self::CONSULTANT => 'External specialist consulting on cases',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::FULL_TIME => 'primary',
            self::PART_TIME => 'info',
            self::CONTRACT => 'warning',
            self::VOLUNTEER => 'secondary',
            self::INTERN => 'gray',
            self::RESIDENT => 'purple',
            self::CONSULTANT => 'teal',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function default(): self
    {
        return self::FULL_TIME;
    }

    public function isActive(): bool
    {
        return in_array($this, [self::FULL_TIME, self::PART_TIME, self::RESIDENT, self::CONSULTANT]);
    }

    public function hasBenefits(): bool
    {
        return in_array($this, [self::FULL_TIME, self::PART_TIME, self::RESIDENT]);
    }

    public function isTemporary(): bool
    {
        return in_array($this, [self::CONTRACT, self::INTERN, self::VOLUNTEER]);
    }
}
