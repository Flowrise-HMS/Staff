<?php

namespace Modules\Staff\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Modules\Core\Models\Department;

interface StaffableContract
{
    public function getStaff(): ?object;

    public function hasStaffProfile(): bool;

    public function isActiveStaff(): bool;

    public function getStaffDepartments(): Collection;

    public function getPrimaryDepartment(): ?Department;

    public function getStaffCredentials(): Collection;

    public function getValidCredentials(): Collection;

    public function getExpiringCredentials(int $days = 30): Collection;

    public function hasValidCredential(string $type): bool;

    public function isCredentialExpiringSoon(string $type, int $days = 30): bool;

    public function getStaffSpecialties(): Collection;

    public function getPrimarySpecialty(): ?object;

    public function canAccessClinicalModule(): bool;

    public function canPerformProcedures(): bool;

    public function canPrescribe(): bool;

    public function isLicensed(): bool;

    public function getDisplayName(): string;

    public function getInitials(): string;
}
