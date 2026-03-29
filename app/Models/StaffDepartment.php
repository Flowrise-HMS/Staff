<?php

namespace Modules\Staff\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\Department;
use Modules\Staff\Database\Factories\StaffDepartmentFactory;

class StaffDepartment extends Model
{
/** @use HasFactory<StaffDepartmentFactory> */

    use HasFactory, HasUuids;

    protected $fillable = [
        'staff_id',
        'department_id',
        'is_primary',
        'start_date',
        'end_date',
        'designation',
        'metadata',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
        'metadata' => 'array',
    ];

    protected static function newFactory(): Factory
    {
        return StaffDepartmentFactory::new();
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function getIsActiveAttribute(): bool
    {
        if ($this->end_date && $this->end_date->isPast()) {
            return false;
        }

        return true;
    }

    public function getDurationMonthsAttribute(): int
    {
        $end = $this->end_date ?? now();

        return (int) $this->start_date->diffInMonths($end);
    }

    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('end_date')
                ->orWhere('end_date', '>=', now());
        });
    }

    public function scopeInactive($query)
    {
        return $query->whereNotNull('end_date')
            ->where('end_date', '<', now());
    }

    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    public function end(): void
    {
        $this->update(['end_date' => now()]);
    }

    public function extend(\DateTimeInterface $newEndDate): void
    {
        $this->update(['end_date' => $newEndDate]);
    }

    public function setAsPrimary(): void
    {
        $this->staff->staffDepartments()
            ->where('id', '!=', $this->id)
            ->update(['is_primary' => false]);

        $this->update(['is_primary' => true]);
    }
}
