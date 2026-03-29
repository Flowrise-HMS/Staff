<?php

namespace Modules\Staff\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum EmploymentStatus: string implements HasColor, HasDescription, HasLabel
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case ON_LEAVE = 'on_leave';
    case SUSPENDED = 'suspended';
    case TERMINATED = 'terminated';
    case PENDING_VERIFICATION = 'pending_verification';

    public function getLabel(): string|Htmlable|null
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::INACTIVE => 'Inactive',
            self::ON_LEAVE => 'On Leave',
            self::SUSPENDED => 'Suspended',
            self::TERMINATED => 'Terminated',
            self::PENDING_VERIFICATION => 'Pending Verification',
        };
    }

    public function getDescription(): ?string
    {
        return match ($this) {
            self::ACTIVE => 'Currently working and can perform duties',
            self::INACTIVE => 'Temporarily not working (e.g., sabbatical)',
            self::ON_LEAVE => 'On approved leave (annual, sick, maternity)',
            self::SUSPENDED => 'Temporarily barred from duties pending investigation',
            self::TERMINATED => 'Employment has ended',
            self::PENDING_VERIFICATION => 'Awaiting credential/document verification',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::ACTIVE => 'success',
            self::INACTIVE => 'gray',
            self::ON_LEAVE => 'info',
            self::SUSPENDED => 'warning',
            self::TERMINATED => 'danger',
            self::PENDING_VERIFICATION => 'purple',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function default(): self
    {
        return self::PENDING_VERIFICATION;
    }

    public function isWorking(): bool
    {
        return $this === self::ACTIVE;
    }

    public function canPerformDuties(): bool
    {
        return $this === self::ACTIVE;
    }

    public function isFinal(): bool
    {
        return $this === self::TERMINATED;
    }

    public function requiresReview(): bool
    {
        return in_array($this, [self::SUSPENDED, self::PENDING_VERIFICATION]);
    }
}
