<?php

namespace Modules\Staff\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Modules\Core\Enums\Title;
use Modules\Core\Models\BaseModel;
use Modules\Core\Models\Department;
use Modules\Core\Traits\HasAddress;
use Modules\Core\Traits\HasContact;
use Modules\Patient\Enums\Gender;
use Modules\Staff\Database\Factories\StaffFactory;
use Modules\Staff\Enums\EmploymentStatus;
use Modules\Staff\Enums\StaffType;

class Staff extends BaseModel
{
    /** @use HasFactory<StaffFactory> */
    use HasAddress, HasContact, HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'branch_id',
        'user_id',
        'staff_number',
        'title',
        'first_name',
        'middle_name',
        'last_name',
        'date_of_birth',
        'gender',
        'address',
        'contact',
        'emergency_contact',
        'staff_type',
        'employment_status',
        'hire_date',
        'termination_date',
        'termination_reason',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'title' => Title::class,
        'gender' => Gender::class,
        'staff_type' => StaffType::class,
        'employment_status' => EmploymentStatus::class,
        'hire_date' => 'date',
        'termination_date' => 'date',
        'date_of_birth' => 'date',
        'address' => 'array',
        'contact' => 'array',
        'emergency_contact' => 'array',
        'metadata' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (Staff $staff) {
            if (empty($staff->staff_number)) {
                $staff->staff_number = static::generateStaffNumber();
            }
        });

        static::saving(function (Staff $staff) {
            if ($staff->employment_status === EmploymentStatus::TERMINATED && empty($staff->termination_date)) {
                $staff->termination_date = now();
            }
        });
    }

    protected static function newFactory(): Factory
    {
        return StaffFactory::new();
    }

    public static function generateStaffNumber(): string
    {
        $prefix = 'STF';
        $year = now()->format('Y');
        $sequence = static::whereYear('created_at', $year)->count() + 1;

        return sprintf('%s-%s-%05d', $prefix, $year, $sequence);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model', User::class), 'user_id');
    }

    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class, 'staff_departments')
            ->using(StaffDepartment::class)
            ->withPivot(['is_primary', 'start_date', 'end_date', 'designation'])
            ->withTimestamps();
    }

    public function primaryDepartment(): BelongsToMany
    {
        return $this->departments()->wherePivot('is_primary', true);
    }

    public function staffDepartments(): HasMany
    {
        return $this->hasMany(StaffDepartment::class);
    }

    public function specialties(): HasMany
    {
        return $this->hasMany(StaffSpecialty::class);
    }

    public function primarySpecialty(): HasMany
    {
        return $this->specialties()->where('is_primary', true);
    }

    public function credentials(): HasMany
    {
        return $this->hasMany(StaffCredential::class);
    }

    public function validCredentials(): HasMany
    {
        return $this->hasMany(StaffCredential::class)
            ->where('status', 'verified');
    }

    public function expiringCredentials(int $days = 30): HasMany
    {
        return $this->credentials()
            ->where('expiry_date', '<=', now()->addDays($days))
            ->where('expiry_date', '>=', now());
    }

    public function scopeActive($query)
    {
        return $query->where('employment_status', EmploymentStatus::ACTIVE);
    }

    public function scopeOnLeave($query)
    {
        return $query->where('employment_status', EmploymentStatus::ON_LEAVE);
    }

    public function scopeTerminated($query)
    {
        return $query->where('employment_status', EmploymentStatus::TERMINATED);
    }

    public function scopeByType($query, StaffType $type)
    {
        return $query->where('staff_type', $type);
    }

    public function scopeByDepartment($query, string $departmentId)
    {
        return $query->whereHas('departments', function ($q) use ($departmentId) {
            $q->where('departments.id', $departmentId);
        });
    }

    public function scopeWithExpiringCredentials($query, int $days = 30)
    {
        return $query->whereHas('credentials', function ($q) use ($days) {
            $q->where('expiry_date', '<=', now()->addDays($days))
                ->where('expiry_date', '>=', now());
        });
    }

    public function getFullNameAttribute(): string
    {
        $parts = array_filter([
            $this->title?->getLabel(),
            $this->first_name,
            $this->middle_name,
            $this->last_name,
        ]);

        return implode(' ', $parts);
    }

    public function getInitialsAttribute(): string
    {
        $initials = '';
        if ($this->first_name) {
            $initials .= strtoupper(substr($this->first_name, 0, 1));
        }
        if ($this->last_name) {
            $initials .= strtoupper(substr($this->last_name, 0, 1));
        }

        return $initials ?: '??';
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->full_name ?: "Staff #{$this->staff_number}";
    }

    public function getAgeAttribute(): ?int
    {
        return $this->date_of_birth?->age;
    }

    public function getTenureYearsAttribute(): float
    {
        if (! $this->hire_date) {
            return 0;
        }

        return (float) number_format($this->hire_date->diffInYears(now()), 1);
    }

    public function getIsActiveAttribute(): bool
    {
        return $this->employment_status === EmploymentStatus::ACTIVE;
    }

    public function getHasUserAccountAttribute(): bool
    {
        return ! is_null($this->user_id);
    }

    public function getIsLicensedAttribute(): bool
    {
        return $this->credentials()
            ->where('status', 'verified')
            ->exists();
    }

    public function hasValidCredential(string $type): bool
    {
        return $this->credentials()
            ->where('credential_type', $type)
            ->where('status', 'verified')
            ->where(function ($query) {
                $query->whereNull('expiry_date')
                    ->orWhere('expiry_date', '>', now());
            })
            ->exists();
    }

    public function isCredentialExpiringSoon(string $type, int $days = 30): bool
    {
        return $this->credentials()
            ->where('credential_type', $type)
            ->where('status', 'verified')
            ->whereBetween('expiry_date', [now(), now()->addDays($days)])
            ->exists();
    }

    public function getPrimaryDepartmentAttribute(): ?Department
    {
        return $this->departments()
            ->wherePivot('is_primary', true)
            ->first();
    }

    public function getActiveDepartmentsAttribute(): Collection
    {
        return $this->departments()
            ->wherePivot('end_date', '>=', now())
            ->orWhereNull('staff_departments.end_date')
            ->get();
    }

    public function assignDepartment(Department $department, bool $isPrimary = false, ?string $designation = null): StaffDepartment
    {
        if ($isPrimary) {
            $this->staffDepartments()->update(['is_primary' => false]);
        }

        return $this->staffDepartments()->create([
            'department_id' => $department->id,
            'is_primary' => $isPrimary,
            'start_date' => now(),
            'designation' => $designation,
        ]);
    }

    public function removeDepartment(Department $department): bool
    {
        return $this->staffDepartments()
            ->where('department_id', $department->id)
            ->delete();
    }

    public function terminate(?string $reason = null): void
    {
        $this->update([
            'employment_status' => EmploymentStatus::TERMINATED,
            'termination_date' => now(),
            'termination_reason' => $reason,
        ]);

        $this->staffDepartments()
            ->whereNull('end_date')
            ->update(['end_date' => now()]);
    }

    public function reactivate(): void
    {
        $this->update([
            'employment_status' => EmploymentStatus::ACTIVE,
            'termination_date' => null,
            'termination_reason' => null,
        ]);
    }
}
