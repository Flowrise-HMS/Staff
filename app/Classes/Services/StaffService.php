<?php

namespace Modules\Staff\Classes\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Event;
use Modules\Staff\Enums\EmploymentStatus;
use Modules\Staff\Enums\StaffType;
use Modules\Staff\Events\StaffDeactivated;
use Modules\Staff\Events\StaffReactivated;
use Modules\Staff\Events\StaffRegistered;
use Modules\Staff\Events\StaffUpdated;
use Modules\Staff\Models\Staff;

class StaffService
{
    public function getAll(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Staff::query();

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('staff_number', 'like', "%{$search}%")
                    ->orWhereJsonContains('contact->email', $search);
            });
        }

        if (! empty($filters['status']) && $filters['status'] instanceof EmploymentStatus) {
            $query->where('employment_status', $filters['status']);
        }

        if (! empty($filters['type']) && $filters['type'] instanceof StaffType) {
            $query->where('staff_type', $filters['type']);
        }

        if (! empty($filters['department'])) {
            $query->byDepartment($filters['department']);
        }

        $sortField = $filters['sort_by'] ?? 'created_at';
        $sortDirection = $filters['sort_direction'] ?? 'desc';
        $query->orderBy($sortField, $sortDirection);

        return $query->paginate($perPage);
    }

    public function getActive(): Collection
    {
        return Staff::active()->get();
    }

    public function find(string $id): ?Staff
    {
        return Staff::find($id);
    }

    public function findById(string $id): ?Staff
    {
        return Staff::find($id);
    }

    public function findByStaffNumber(string $staffNumber): ?Staff
    {
        return Staff::where('staff_number', $staffNumber)->first();
    }

    public function create(array $data): Staff
    {
        $staff = Staff::create($data);
        Event::dispatch(new StaffRegistered($staff));

        return $staff;
    }

    public function update(Staff $staff, array $data): Staff
    {
        $changedFields = array_keys($data);
        $staff->update($data);
        Event::dispatch(new StaffUpdated($staff, $changedFields));

        return $staff->fresh();
    }

    public function terminate(Staff $staff, ?string $reason = null): Staff
    {
        $staff->terminate($reason);
        Event::dispatch(new StaffDeactivated($staff, $reason));

        return $staff->fresh();
    }

    public function reactivate(Staff $staff): Staff
    {
        $staff->reactivate();
        Event::dispatch(new StaffReactivated($staff));

        return $staff->fresh();
    }

    public function linkUserAccount(Staff $staff, int $userId): Staff
    {
        $existingStaff = Staff::where('user_id', $userId)->first();

        if ($existingStaff && $existingStaff->id !== $staff->id) {
            throw new \Exception('User is already linked to another staff member');
        }

        $staff->update(['user_id' => $userId]);

        return $staff->fresh();
    }

    public function unlinkUserAccount(Staff $staff): Staff
    {
        $staff->update(['user_id' => null]);

        return $staff->fresh();
    }

    public function getStatistics(): array
    {
        $total = Staff::count();
        $active = Staff::active()->count();
        $inactive = Staff::where('employment_status', EmploymentStatus::INACTIVE)->count();
        $onLeave = Staff::onLeave()->count();
        $terminated = Staff::terminated()->count();

        $byType = Staff::query()
            ->select('staff_type')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('staff_type')
            ->pluck('count', 'staff_type')
            ->toArray();

        $withCredentials = Staff::whereHas('credentials', function ($query) {
            $query->where('status', 'verified');
        })->count();

        return [
            'total' => $total,
            'active' => $active,
            'inactive' => $inactive,
            'on_leave' => $onLeave,
            'terminated' => $terminated,
            'with_credentials' => $withCredentials,
            'by_type' => $byType,
        ];
    }
}
