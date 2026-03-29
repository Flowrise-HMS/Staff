<?php

namespace Modules\Staff\Classes\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Event;
use Modules\Staff\Enums\CredentialStatus;
use Modules\Staff\Events\CredentialExpired;
use Modules\Staff\Events\CredentialRejected;
use Modules\Staff\Events\CredentialRenewed;
use Modules\Staff\Events\CredentialVerified;
use Modules\Staff\Models\Staff;
use Modules\Staff\Models\StaffCredential;

class StaffCredentialService
{
    public function create(Staff $staff, array $data): StaffCredential
    {
        return $staff->credentials()->create($data);
    }

    public function update(StaffCredential $credential, array $data): StaffCredential
    {
        $credential->update($data);

        return $credential->fresh();
    }

    public function verify(StaffCredential $credential, int $verifierId, ?string $notes = null): StaffCredential
    {
        $credential->verify($verifierId, $notes);
        Event::dispatch(new CredentialVerified($credential, $verifierId, $notes));

        return $credential->fresh();
    }

    public function reject(StaffCredential $credential, int $rejectorId, string $reason): StaffCredential
    {
        $credential->reject($rejectorId, $reason);
        Event::dispatch(new CredentialRejected($credential, $rejectorId, $reason));

        return $credential->fresh();
    }

    public function renew(StaffCredential $credential, \DateTimeInterface $newExpiryDate, ?string $notes = null): StaffCredential
    {
        $previousExpiry = $credential->expiry_date;
        $credential->renew($newExpiryDate, $notes);
        Event::dispatch(new CredentialRenewed($credential, $previousExpiry, $newExpiryDate));

        return $credential->fresh();
    }

    public function bulkVerify(array $credentialIds, int $verifierId): int
    {
        $count = 0;
        $credentials = StaffCredential::whereIn('id', $credentialIds)
            ->whereIn('status', [CredentialStatus::PENDING, CredentialStatus::UNDER_REVIEW])
            ->get();

        foreach ($credentials as $credential) {
            $this->verify($credential, $verifierId, 'Bulk verification');
            $count++;
        }

        return $count;
    }

    public function getExpiringCredentials(int $days = 30): Collection
    {
        return StaffCredential::expiringSoon($days)->with('staff')->get();
    }

    public function getPendingVerification(): Collection
    {
        return StaffCredential::pending()->with('staff')->get();
    }

    public function processExpiredCredentials(): int
    {
        $count = 0;

        $expiredCredentials = StaffCredential::verified()
            ->where('expiry_date', '<', now())
            ->get();

        foreach ($expiredCredentials as $credential) {
            $credential->markAsExpired();
            Event::dispatch(new CredentialExpired($credential));
            $count++;
        }

        return $count;
    }

    public function getCredentialStatistics(): array
    {
        $total = StaffCredential::count();
        $pending = StaffCredential::pending()->count();
        $verified = StaffCredential::verified()->count();
        $expired = StaffCredential::expired()->count();
        $expiringSoon = StaffCredential::expiringSoon(30)->count();

        $byType = StaffCredential::query()
            ->select('credential_type')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('credential_type')
            ->pluck('count', 'credential_type')
            ->toArray();

        return [
            'total' => $total,
            'pending_verification' => $pending,
            'verified' => $verified,
            'expired' => $expired,
            'expiring_soon' => $expiringSoon,
            'by_type' => $byType,
        ];
    }

    public function delete(StaffCredential $credential): bool
    {
        return $credential->delete();
    }

    public function getStaffCredentials(Staff $staff): Collection
    {
        return $staff->credentials()->with('verifiedBy')->get();
    }
}
