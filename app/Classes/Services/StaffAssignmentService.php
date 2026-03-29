<?php

namespace Modules\Staff\Classes\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Core\Models\Department;
use Modules\Staff\Models\Staff;
use Modules\Staff\Models\StaffDepartment;
use Modules\Staff\Models\StaffSpecialty;

class StaffAssignmentService
{
    public function assignToDepartment(Staff $staff, Department $department, bool $isPrimary = false, ?string $designation = null): StaffDepartment
    {
        if ($isPrimary) {
            $staff->staffDepartments()->where('department_id', '!=', $department->id)
                ->update(['is_primary' => false]);
        }

        $existing = $staff->staffDepartments()
            ->where('department_id', $department->id)
            ->whereNull('end_date')
            ->first();

        if ($existing) {
            if ($isPrimary && ! $existing->is_primary) {
                $existing->update(['is_primary' => true]);
            }

            return $existing;
        }

        return $staff->staffDepartments()->create([
            'department_id' => $department->id,
            'is_primary' => $isPrimary,
            'start_date' => now(),
            'designation' => $designation,
        ]);
    }

    public function removeFromDepartment(Staff $staff, Department $department): bool
    {
        $assignment = $staff->staffDepartments()
            ->where('department_id', $department->id)
            ->whereNull('end_date')
            ->first();

        if ($assignment) {
            $assignment->update(['end_date' => now()]);

            if ($assignment->is_primary) {
                $nextPrimary = $staff->staffDepartments()
                    ->where('department_id', '!=', $department->id)
                    ->whereNull('end_date')
                    ->first();

                if ($nextPrimary) {
                    $nextPrimary->update(['is_primary' => true]);
                }
            }

            return true;
        }

        return false;
    }

    public function transferToDepartment(Staff $staff, Department $fromDepartment, Department $toDepartment): StaffDepartment
    {
        return DB::transaction(function () use ($staff, $fromDepartment, $toDepartment) {
            $this->removeFromDepartment($staff, $fromDepartment);

            return $this->assignToDepartment($staff, $toDepartment, true);
        });
    }

    public function bulkAssignDepartments(Staff $staff, array $departmentIds, bool $setFirstAsPrimary = true): \Illuminate\Support\Collection
    {
        return DB::transaction(function () use ($staff, $departmentIds, $setFirstAsPrimary) {
            $departments = Department::whereIn('id', $departmentIds)->get();
            $assignments = collect();

            foreach ($departments as $index => $department) {
                $isPrimary = $setFirstAsPrimary && $index === 0;
                $assignment = $this->assignToDepartment($staff, $department, $isPrimary);
                $assignments->push($assignment);
            }

            return $assignments;
        });
    }

    public function getStaffDepartments(Staff $staff): Collection
    {
        return $staff->staffDepartments()
            ->whereNull('end_date')
            ->with('department')
            ->get()
            ->map(fn ($sd) => $sd->department);
    }

    public function addSpecialty(
        Staff $staff,
        string $name,
        string $code,
        ?\DateTimeInterface $certificationDate = null,
        ?\DateTimeInterface $expiryDate = null,
        ?string $issuingAuthority = null,
        ?string $licenseNumber = null,
        bool $isPrimary = false
    ): StaffSpecialty {
        if ($isPrimary) {
            $staff->specialties()->update(['is_primary' => false]);
        }

        return $staff->specialties()->create([
            'specialty_name' => $name,
            'specialty_code' => $code,
            'certification_date' => $certificationDate,
            'expiry_date' => $expiryDate,
            'issuing_authority' => $issuingAuthority,
            'license_number' => $licenseNumber,
            'is_primary' => $isPrimary,
        ]);
    }

    public function removeSpecialty(StaffSpecialty $specialty): bool
    {
        $wasPrimary = $specialty->is_primary;
        $staff = $specialty->staff;

        $result = $specialty->delete();

        if ($wasPrimary) {
            $nextPrimary = $staff->specialties()->first();
            if ($nextPrimary) {
                $nextPrimary->update(['is_primary' => true]);
            }
        }

        return $result;
    }

    public function getStaffSpecialties(Staff $staff): Collection
    {
        return $staff->specialties()->orderBy('is_primary', 'desc')->get();
    }

    public function setPrimarySpecialty(Staff $staff, StaffSpecialty $specialty): StaffSpecialty
    {
        if ($specialty->staff_id !== $staff->id) {
            throw new \InvalidArgumentException('Specialty does not belong to this staff member');
        }

        $staff->specialties()->update(['is_primary' => false]);

        $specialty->update(['is_primary' => true]);

        return $specialty->fresh();
    }

    public function getStaffAssignmentsSummary(Staff $staff): array
    {
        $departments = $this->getStaffDepartments($staff);
        $specialties = $this->getStaffSpecialties($staff);

        $primaryDepartment = $departments->first(fn ($d) => $d->pivot && $d->pivot->is_primary);

        return [
            'departments' => [
                'total' => $departments->count(),
                'primary' => $primaryDepartment?->id,
            ],
            'specialties' => [
                'total' => $specialties->count(),
                'primary' => $specialties->first()?->id,
            ],
        ];
    }
}
