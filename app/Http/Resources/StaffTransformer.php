<?php

namespace Modules\Staff\Http\Resources;

use Illuminate\Http\Request;
use Modules\Core\Http\Resources\ApiTransformer;
use Modules\Staff\Models\Staff;

/**
 * @property Staff $resource
 */
class StaffTransformer extends ApiTransformer
{
    public function toArray(Request $request): array
    {
        return $this->filterFields([
            'id' => $this->resource->id,
            'staff_number' => $this->resource->staff_number,
            'title' => $this->resource->title?->value,
            'first_name' => $this->resource->first_name,
            'middle_name' => $this->resource->middle_name,
            'last_name' => $this->resource->last_name,
            'gender' => $this->resource->gender?->value,
            'staff_type' => $this->resource->staff_type?->value,
            'employment_status' => $this->resource->employment_status?->value,
            'hire_date' => $this->resource->hire_date?->format('Y-m-d'),
            'termination_date' => $this->resource->termination_date?->format('Y-m-d'),
            'contact' => $this->contact(),
            'created_at' => $this->resource->created_at?->toIso8601String(),
            'updated_at' => $this->resource->updated_at?->toIso8601String(),
        ]);
    }

    /**
     * Only the two directory-relevant contact keys are exposed. The stored blob is
     * free-form, so returning it whole would leak whatever an admin happened to put
     * in it.
     *
     * @return array{phone: string|null, email: string|null}
     */
    private function contact(): array
    {
        $contact = $this->resource->contact ?? [];

        return [
            'phone' => $contact['phone'] ?? null,
            'email' => $contact['email'] ?? null,
        ];
    }

    /**
     * Deliberately withheld: `date_of_birth`, `address`, `emergency_contact`,
     * `notes`, `metadata`, `user_id`, `zk_user_id`, `termination_reason`. These are
     * employee personal data, not directory data, and no consumer of this endpoint
     * has a stated need for them. `branch_id` is withheld because the caller is
     * already pinned to one branch.
     */
    protected function allowedFields(): array
    {
        return [
            'id',
            'staff_number',
            'title',
            'first_name',
            'middle_name',
            'last_name',
            'gender',
            'staff_type',
            'employment_status',
            'hire_date',
            'termination_date',
            'contact',
            'created_at',
            'updated_at',
        ];
    }
}
