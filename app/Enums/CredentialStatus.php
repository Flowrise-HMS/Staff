<?php

namespace Modules\Staff\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum CredentialStatus: string implements HasColor, HasDescription, HasLabel
{
    case PENDING = 'pending';
    case VERIFIED = 'verified';
    case EXPIRED = 'expired';
    case REJECTED = 'rejected';
    case REVOKED = 'revoked';
    case UNDER_REVIEW = 'under_review';

    public function getLabel(): string|Htmlable|null
    {
        return match ($this) {
            self::PENDING => 'Pending Verification',
            self::VERIFIED => 'Verified',
            self::EXPIRED => 'Expired',
            self::REJECTED => 'Rejected',
            self::REVOKED => 'Revoked',
            self::UNDER_REVIEW => 'Under Review',
        };
    }

    public function getDescription(): ?string
    {
        return match ($this) {
            self::PENDING => 'Awaiting verification by administrator',
            self::VERIFIED => 'Credential has been verified and is valid',
            self::EXPIRED => 'Credential has passed its expiry date',
            self::REJECTED => 'Credential verification was rejected',
            self::REVOKED => 'Credential was valid but has been revoked',
            self::UNDER_REVIEW => 'Credential is currently under review',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::VERIFIED => 'success',
            self::PENDING, self::UNDER_REVIEW => 'warning',
            self::EXPIRED => 'danger',
            self::REJECTED, self::REVOKED => 'danger',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function default(): self
    {
        return self::PENDING;
    }

    public function isValid(): bool
    {
        return $this === self::VERIFIED;
    }

    public function requiresAction(): bool
    {
        return in_array($this, [self::PENDING, self::EXPIRED, self::UNDER_REVIEW]);
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::REJECTED, self::REVOKED, self::EXPIRED]);
    }
}
