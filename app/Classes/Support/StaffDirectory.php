<?php

namespace Modules\Staff\Classes\Support;

use Illuminate\Database\Eloquent\Builder;
use Modules\Core\Contracts\StaffDirectoryContract;
use Modules\Staff\Enums\EmploymentStatus;
use Modules\Staff\Models\Staff;

/**
 * A user counts as staff when linked to a staff profile that is neither
 * deleted nor terminated.
 */
class StaffDirectory implements StaffDirectoryContract
{
    public function constrainToStaff(Builder $users): Builder
    {
        $staff = new Staff;

        return $users->whereExists(fn ($query) => $query
            ->selectRaw('1')
            ->from($staff->getTable())
            ->whereColumn($staff->qualifyColumn('user_id'), $users->getModel()->getQualifiedKeyName())
            ->whereNull($staff->getQualifiedDeletedAtColumn())
            ->where($staff->qualifyColumn('employment_status'), '!=', EmploymentStatus::TERMINATED->value));
    }

    public function isStaff(int|string $userId): bool
    {
        return Staff::withoutGlobalScopes()
            ->whereNull('deleted_at')
            ->where('user_id', $userId)
            ->where('employment_status', '!=', EmploymentStatus::TERMINATED->value)
            ->exists();
    }
}
