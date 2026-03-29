<?php

namespace Modules\Staff\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Staff\Database\Factories\StaffSpecialtyFactory;

class StaffSpecialty extends Model
{
/** @use HasFactory<StaffSpecialtyFactory> */

    use HasFactory, HasUuids;

    protected $fillable = [
        'staff_id',
        'specialty_name',
        'specialty_code',
        'description',
        'certification_date',
        'expiry_date',
        'issuing_body',
        'certificate_number',
        'is_primary',
        'metadata',
    ];

    protected $casts = [
        'certification_date' => 'date',
        'expiry_date' => 'date',
        'is_primary' => 'boolean',
        'metadata' => 'array',
    ];

    protected static function newFactory(): Factory
    {
        return StaffSpecialtyFactory::new();
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expiry_date')
                ->orWhere('expiry_date', '>=', now());
        });
    }

    public function scopeExpired($query)
    {
        return $query->whereNotNull('expiry_date')
            ->where('expiry_date', '<', now());
    }

    public function scopeExpiringSoon($query, int $days = 30)
    {
        return $query->whereBetween('expiry_date', [now(), now()->addDays($days)]);
    }

    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }

    public function getIsExpiringSoonAttribute(): bool
    {
        if (! $this->expiry_date) {
            return false;
        }

        return $this->expiry_date->isBetween(now(), now()->addDays(30));
    }

    public function getDaysUntilExpiryAttribute(): ?int
    {
        if (! $this->expiry_date) {
            return null;
        }

        return now()->diffInDays($this->expiry_date, false);
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->specialty_code
            ? "{$this->specialty_name} ({$this->specialty_code})"
            : $this->specialty_name;
    }

    public function setAsPrimary(): void
    {
        $this->staff->specialties()
            ->where('id', '!=', $this->id)
            ->update(['is_primary' => false]);

        $this->update(['is_primary' => true]);
    }

    public function markAsExpired(): void
    {
        $this->update(['expiry_date' => now()->subDay()]);
    }

    public function renew(\DateTimeInterface $newExpiryDate): void
    {
        $this->update(['expiry_date' => $newExpiryDate]);
    }
}
