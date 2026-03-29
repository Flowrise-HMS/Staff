<?php

namespace Modules\Staff\Traits;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;
use Modules\Staff\Enums\CredentialStatus;
use Modules\Staff\Enums\CredentialType;
use Modules\Staff\Models\StaffCredential;

trait HasCredentials
{
    public function credentials(): MorphMany
    {
        return $this->morphMany(StaffCredential::class, 'staff', 'staff_id');
    }

    public function hasCredentials(): bool
    {
        return $this->credentials()->exists();
    }

    public function getCredential(CredentialType $type): ?StaffCredential
    {
        return $this->credentials()->byType($type)->first();
    }

    public function hasCredential(CredentialType $type): bool
    {
        return $this->credentials()->byType($type)->exists();
    }

    public function hasValidCredential(CredentialType $type): bool
    {
        $credential = $this->getCredential($type);

        if (! $credential) {
            return false;
        }

        if ($credential->status !== CredentialStatus::VERIFIED) {
            return false;
        }

        if ($credential->expiry_date && $credential->expiry_date->isPast()) {
            return false;
        }

        return true;
    }

    public function isCredentialExpired(CredentialType $type): bool
    {
        $credential = $this->getCredential($type);

        return $credential && $credential->is_expired;
    }

    public function isCredentialExpiringSoon(CredentialType $type, int $days = 30): bool
    {
        $credential = $this->getCredential($type);

        return $credential && $credential->is_expiring_soon;
    }

    public function getDaysUntilCredentialExpiry(CredentialType $type): ?int
    {
        $credential = $this->getCredential($type);

        return $credential?->days_until_expiry;
    }

    public function addCredential(
        CredentialType $type,
        ?string $credentialNumber = null,
        ?\DateTimeInterface $expiryDate = null,
        ?string $issuingAuthority = null,
        ?string $issuingCountry = null
    ): StaffCredential {
        return $this->credentials()->create([
            'credential_type' => $type,
            'credential_number' => $credentialNumber,
            'expiry_date' => $expiryDate,
            'issuing_authority' => $issuingAuthority,
            'issuing_country' => $issuingCountry,
            'status' => CredentialStatus::PENDING,
        ]);
    }

    public function verifyCredential(CredentialType $type, int $verifiedBy, ?string $notes = null): bool
    {
        $credential = $this->getCredential($type);

        if (! $credential) {
            return false;
        }

        $credential->verify($verifiedBy, $notes);

        return true;
    }

    public function rejectCredential(CredentialType $type, int $rejectedBy, string $reason): bool
    {
        $credential = $this->getCredential($type);

        if (! $credential) {
            return false;
        }

        $credential->reject($rejectedBy, $reason);

        return true;
    }

    public function revokeCredential(CredentialType $type, int $revokedBy, string $reason): bool
    {
        $credential = $this->getCredential($type);

        if (! $credential) {
            return false;
        }

        $credential->revoke($revokedBy, $reason);

        return true;
    }

    public function renewCredential(CredentialType $type, \DateTimeInterface $newExpiryDate, ?string $notes = null): bool
    {
        $credential = $this->getCredential($type);

        if (! $credential) {
            return false;
        }

        $credential->renew($newExpiryDate, $notes);

        return true;
    }

    public function getPendingCredentials(): Collection
    {
        return $this->credentials()->pending()->get();
    }

    public function getVerifiedCredentials(): Collection
    {
        return $this->credentials()->verified()->get();
    }

    public function getExpiredCredentials(): Collection
    {
        return $this->credentials()->expired()->get();
    }

    public function getExpiringCredentials(int $days = 30): Collection
    {
        return $this->credentials()->expiringSoon($days)->get();
    }

    public function getAllValidCredentials(): Collection
    {
        return $this->credentials()->get()->filter(function (StaffCredential $credential) {
            return $credential->is_valid;
        });
    }

    public function isLicensed(): bool
    {
        return $this->credentials()->verified()->exists();
    }
}
