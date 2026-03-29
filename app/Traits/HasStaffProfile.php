<?php

namespace Modules\Staff\Traits;

use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Collection;
use Modules\Core\Models\Department;
use Modules\Staff\Enums\CredentialType;
use Modules\Staff\Enums\EmploymentStatus;
use Modules\Staff\Models\Staff;
use Modules\Staff\Models\StaffDepartment;
use Modules\Staff\Models\StaffSpecialty;

trait HasStaffProfile
{
    public function staffProfile(): MorphOne
    {
        return $this->morphOne(Staff::class, 'user', 'user_id');
    }

    public function staff(): ?Staff
    {
        return $this->staffProfile;
    }

    public function hasStaffProfile(): bool
    {
        return $this->staffProfile()->exists();
    }

    public function isStaff(): bool
    {
        return $this->hasStaffProfile();
    }

    public function isActiveStaff(): bool
    {
        return $this->staff?->employment_status === EmploymentStatus::ACTIVE;
    }

    public function getStaffDepartments(): Collection
    {
        return $this->staff?->departments ?? collect();
    }

    public function getPrimaryDepartment(): ?Department
    {
        return $this->staff?->primaryDepartment;
    }

    public function getStaffSpecialties(): Collection
    {
        return $this->staff?->specialties ?? collect();
    }

    public function getPrimarySpecialty(): ?StaffSpecialty
    {
        return $this->staff?->primarySpecialty->first();
    }

    public function assignToDepartment(Department $department, bool $isPrimary = false, ?string $designation = null): ?StaffDepartment
    {
        if (! $this->staff) {
            return null;
        }

        return $this->staff->assignDepartment($department, $isPrimary, $designation);
    }

    public function removeFromDepartment(Department $department): bool
    {
        if (! $this->staff) {
            return false;
        }

        return $this->staff->removeDepartment($department);
    }

    public function canAccessClinicalModule(): bool
    {
        if (! $this->staff) {
            return false;
        }

        return $this->staff->is_active && $this->staff->is_licensed;
    }

    public function canPerformProcedures(): bool
    {
        if (! $this->staff) {
            return false;
        }

        return $this->staff->is_active && $this->staff->specialties()->exists();
    }

    public function canPrescribe(): bool
    {
        if (! $this->staff) {
            return false;
        }

        return $this->staff->hasValidCredential(CredentialType::DEA_REGISTRATION->value)
            || $this->staff->hasValidCredential(CredentialType::MEDICAL_LICENSE->value);
    }

    public function getStaffDisplayName(): string
    {
        return $this->staff?->full_name ?? $this->name;
    }

    public function getStaffInitials(): string
    {
        return $this->staff?->initials ?? $this->initials();
    }
}
