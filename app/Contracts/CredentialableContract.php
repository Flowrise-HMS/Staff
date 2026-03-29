<?php

namespace Modules\Staff\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Modules\Staff\Enums\CredentialStatus;
use Modules\Staff\Enums\CredentialType;

interface CredentialableContract
{
    public function getCredentials(): Collection;

    public function getCredential(string $type): ?object;

    public function addCredential(
        CredentialType $type,
        string $credentialNumber,
        ?\DateTimeInterface $expiryDate = null,
        ?string $issuingAuthority = null,
        ?string $issuingCountry = null
    ): object;

    public function updateCredential(
        string $type,
        array $data
    ): bool;

    public function verifyCredential(
        string $type,
        int $verifiedBy,
        ?string $notes = null
    ): bool;

    public function rejectCredential(
        string $type,
        int $rejectedBy,
        string $reason
    ): bool;

    public function revokeCredential(
        string $type,
        int $revokedBy,
        string $reason
    ): bool;

    public function renewCredential(
        string $type,
        \DateTimeInterface $newExpiryDate,
        ?string $notes = null
    ): bool;

    public function getPendingCredentials(): Collection;

    public function getExpiredCredentials(): Collection;

    public function getExpiringCredentials(int $days = 30): Collection;

    public function hasValidCredential(CredentialType $type): bool;

    public function isCredentialExpired(CredentialType $type): bool;

    public function isCredentialExpiringSoon(CredentialType $type, int $days = 30): bool;

    public function getDaysUntilCredentialExpiry(CredentialType $type): ?int;

    public function getCredentialStatus(CredentialType $type): CredentialStatus;
}
