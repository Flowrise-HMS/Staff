<?php

namespace Modules\Staff\Classes\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Modules\Staff\Enums\EmploymentStatus;
use Modules\Staff\Enums\StaffType;
use Modules\Staff\Models\Staff;

class StaffSearchService
{
    protected array $searchableFields = [
        'staff_number',
        'first_name',
        'middle_name',
        'last_name',
        'contact->email',
        'contact->phone',
        'emergency_contact->name',
        'emergency_contact->phone',
    ];

    protected array $searchableRelations = [
        'departments' => ['name', 'code'],
        'specialties' => ['specialty_name', 'specialty_code'],
        'credentials' => ['credential_number'],
        'user' => ['email', 'name'],
    ];

    public function getSearchableFields(): array
    {
        return $this->searchableFields;
    }

    public function getSearchableRelations(): array
    {
        return $this->searchableRelations;
    }

    public function search(string $term, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Staff::query()->with(['departments', 'specialties', 'credentials']);

        $this->applySearch($query, $term);
        $this->applyFilters($query, $filters);

        return $query->paginate($perPage);
    }

    public function searchBasic(string $term): Collection
    {
        $query = Staff::query();

        $this->applySearch($query, $term);

        return $query->limit(20)->get();
    }

    public function searchByStaffNumber(string $staffNumber): ?Staff
    {
        return Staff::where('staff_number', $staffNumber)->first();
    }

    public function searchByCredential(string $credentialNumber): Collection
    {
        return Staff::whereHas('credentials', function ($query) use ($credentialNumber) {
            $query->where('credential_number', 'like', "%{$credentialNumber}%");
        })->with('credentials')->get();
    }

    public function searchByDepartment(string $departmentId): Collection
    {
        return Staff::whereHas('departments', function ($query) use ($departmentId) {
            $query->where('departments.id', $departmentId);
        })->with('departments')->get();
    }

    public function searchBySpecialty(string $specialtyName): Collection
    {
        return Staff::whereHas('specialties', function ($query) use ($specialtyName) {
            $query->where('specialty_name', 'like', "%{$specialtyName}%")
                ->orWhere('specialty_code', 'like', "%{$specialtyName}%");
        })->with('specialties')->get();
    }

    public function searchWithExpiringCredentials(int $days = 30): Collection
    {
        return Staff::withExpiringCredentials($days)
            ->with(['credentials' => function ($query) use ($days) {
                $query->whereBetween('expiry_date', [now(), now()->addDays($days)]);
            }])
            ->get();
    }

    public function searchActive(): Collection
    {
        return Staff::active()->with(['departments', 'specialties'])->get();
    }

    public function searchByType(StaffType $type): Collection
    {
        return Staff::byType($type)->with(['departments', 'specialties'])->get();
    }

    public function searchByStatus(EmploymentStatus $status): Collection
    {
        return Staff::where('employment_status', $status)
            ->with(['departments', 'specialties'])
            ->get();
    }

    public function applySearch(Builder $query, string $term): Builder
    {
        if (empty($term)) {
            return $query;
        }

        $term = trim($term);

        return $query->where(function ($q) use ($term) {
            foreach ($this->searchableFields as $field) {
                if (str_contains($field, '->')) {
                    $q->orWhereJsonContains($field, $term);
                } else {
                    $q->orWhere($field, 'like', "%{$term}%");
                }
            }

            foreach ($this->searchableRelations as $relation => $fields) {
                $q->orWhereHas($relation, function ($relationQuery) use ($fields, $term) {
                    foreach ($fields as $field) {
                        $relationQuery->orWhere($field, 'like', "%{$term}%");
                    }
                });
            }
        });
    }

    public function applyFilters(Builder $query, array $filters): Builder
    {
        if (! empty($filters['status']) && $filters['status'] instanceof EmploymentStatus) {
            $query->where('employment_status', $filters['status']);
        }

        if (! empty($filters['type']) && $filters['type'] instanceof StaffType) {
            $query->where('staff_type', $filters['type']);
        }

        if (! empty($filters['department'])) {
            $query->byDepartment($filters['department']);
        }

        if (! empty($filters['gender'])) {
            $query->where('gender', $filters['gender']);
        }

        if (! empty($filters['hire_date_from'])) {
            $query->whereDate('hire_date', '>=', $filters['hire_date_from']);
        }

        if (! empty($filters['hire_date_to'])) {
            $query->whereDate('hire_date', '<=', $filters['hire_date_to']);
        }

        if (! empty($filters['has_user']) && $filters['has_user'] === true) {
            $query->whereNotNull('user_id');
        }

        if (! empty($filters['has_credentials']) && $filters['has_credentials'] === true) {
            $query->whereHas('credentials', function ($q) {
                $q->where('status', 'verified');
            });
        }

        return $query;
    }

    public function getSuggestions(string $term, int $limit = 10): Collection
    {
        return Staff::query()
            ->select(['id', 'staff_number', 'first_name', 'last_name'])
            ->where(function ($q) use ($term) {
                $q->where('staff_number', 'like', "%{$term}%")
                    ->orWhere('first_name', 'like', "%{$term}%")
                    ->orWhere('last_name', 'like', "%{$term}%");
            })
            ->limit($limit)
            ->get()
            ->map(function ($staff) {
                return [
                    'id' => $staff->id,
                    'value' => $staff->staff_number,
                    'label' => $staff->full_name,
                ];
            });
    }

    public function advancedSearch(array $criteria): LengthAwarePaginator
    {
        $query = Staff::query()->with(['departments', 'specialties', 'credentials']);

        if (! empty($criteria['term'])) {
            $this->applySearch($query, $criteria['term']);
        }

        if (! empty($criteria['filters'])) {
            $this->applyFilters($query, $criteria['filters']);
        }

        if (! empty($criteria['sort_by'])) {
            $direction = $criteria['sort_direction'] ?? 'asc';
            $query->orderBy($criteria['sort_by'], $direction);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $perPage = $criteria['per_page'] ?? 15;

        return $query->paginate($perPage);
    }
}
