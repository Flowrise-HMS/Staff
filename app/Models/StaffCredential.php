<?php

namespace Modules\Staff\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Staff\Database\Factories\StaffCredentialFactory;
use Modules\Staff\Enums\CredentialStatus;
use Modules\Staff\Enums\CredentialType;

class StaffCredential extends Model
{
    /** @use HasFactory<StaffCredentialFactory> */
    use HasFactory, HasUuids;

    protected $fillable = [
        'staff_id',
        'credential_type',
        'credential_number',
        'issuing_authority',
        'issuing_country',
        'issuing_state',
        'issue_date',
        'expiry_date',
        'status',
        'verified_by',
        'verified_at',
        'verification_notes',
        'rejection_reason',
        'document_path',
        'metadata',
    ];

    protected $casts = [
        'credential_type' => CredentialType::class,
        'status' => CredentialStatus::class,
        'issue_date' => 'date',
        'expiry_date' => 'date',
        'verified_at' => 'datetime',
        'metadata' => 'array',
    ];

    protected static function newFactory(): Factory
    {
        return StaffCredentialFactory::new();
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model', User::class), 'verified_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', CredentialStatus::PENDING);
    }

    public function scopeVerified($query)
    {
        return $query->where('status', CredentialStatus::VERIFIED);
    }

    public function scopeExpired($query)
    {
        return $query->where('status', CredentialStatus::EXPIRED);
    }

    public function scopeExpiringSoon($query, int $days = 30)
    {
        return $query->where('expiry_date', '<=', now()->addDays($days))
            ->where('expiry_date', '>=', now())
            ->where('status', CredentialStatus::VERIFIED);
    }

    public function scopeByType($query, CredentialType $type)
    {
        return $query->where('credential_type', $type);
    }

    public function scopeRequiresVerification($query)
    {
        return $query->whereIn('status', [
            CredentialStatus::PENDING,
            CredentialStatus::UNDER_REVIEW,
        ]);
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

    public function getIsValidAttribute(): bool
    {
        if ($this->status !== CredentialStatus::VERIFIED) {
            return false;
        }

        if ($this->expiry_date && $this->expiry_date->isPast()) {
            return false;
        }

        return true;
    }

    public function getDisplayStatusAttribute(): string
    {
        if ($this->status === CredentialStatus::VERIFIED) {
            if ($this->is_expired) {
                return 'Expired';
            }
            if ($this->is_expiring_soon) {
                return 'Expiring Soon';
            }

            return 'Valid';
        }

        return $this->status->getLabel();
    }

    public function verify(int $verifiedBy, ?string $notes = null): void
    {
        $this->update([
            'status' => CredentialStatus::VERIFIED,
            'verified_by' => $verifiedBy,
            'verified_at' => now(),
            'verification_notes' => $notes,
        ]);
    }

    public function reject(int $rejectedBy, string $reason): void
    {
        $this->update([
            'status' => CredentialStatus::REJECTED,
            'verified_by' => $rejectedBy,
            'verified_at' => now(),
            'rejection_reason' => $reason,
        ]);
    }

    public function markAsExpired(): void
    {
        $this->update(['status' => CredentialStatus::EXPIRED]);
    }

    public function markUnderReview(): void
    {
        $this->update(['status' => CredentialStatus::UNDER_REVIEW]);
    }

    public function revoke(int $revokedBy, string $reason): void
    {
        $this->update([
            'status' => CredentialStatus::REVOKED,
            'verification_notes' => $reason,
        ]);
    }

    public function renew(\DateTimeInterface $newExpiryDate, ?string $notes = null): void
    {
        $this->update([
            'expiry_date' => $newExpiryDate,
            'status' => CredentialStatus::VERIFIED,
            'verification_notes' => $notes,
        ]);
    }

    public function getIssuingLocationAttribute(): string
    {
        $parts = array_filter([
            $this->issuing_state,
            $this->issuing_country,
        ]);

        return implode(', ', $parts) ?: 'Unknown';
    }

    public function requiresExpiry(): bool
    {
        return $this->credential_type->requiresExpiry();
    }

    public function isLifeSupport(): bool
    {
        return $this->credential_type->isLifeSupport();
    }
}
