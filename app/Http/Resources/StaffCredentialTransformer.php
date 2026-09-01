<?php

namespace Modules\Staff\Http\Resources;

use Illuminate\Http\Request;
use Modules\Core\Http\Resources\ApiTransformer;
use Modules\Staff\Models\StaffCredential;

/**
 * @property StaffCredential $resource
 */
class StaffCredentialTransformer extends ApiTransformer
{
    public function toArray(Request $request): array
    {
        return $this->filterFields([
            'id' => $this->resource->id,
            'staff_id' => $this->resource->staff_id,
            'credential_type' => $this->resource->credential_type?->value,
            'credential_number' => $this->resource->credential_number,
            'issuing_authority' => $this->resource->issuing_authority,
            'issuing_country' => $this->resource->issuing_country,
            'issuing_state' => $this->resource->issuing_state,
            'issue_date' => $this->resource->issue_date?->format('Y-m-d'),
            'expiry_date' => $this->resource->expiry_date?->format('Y-m-d'),
            'status' => $this->resource->status?->value,
            'verified_at' => $this->resource->verified_at?->toIso8601String(),
            'created_at' => $this->resource->created_at?->toIso8601String(),
            'updated_at' => $this->resource->updated_at?->toIso8601String(),
        ]);
    }

    /**
     * Deliberately withheld: `document_path` (discloses internal storage layout),
     * `verification_notes` and `rejection_reason` (internal review commentary about
     * an employee), `verified_by` (a user id with no directory meaning here), and
     * `metadata` (free-form).
     */
    protected function allowedFields(): array
    {
        return [
            'id',
            'staff_id',
            'credential_type',
            'credential_number',
            'issuing_authority',
            'issuing_country',
            'issuing_state',
            'issue_date',
            'expiry_date',
            'status',
            'verified_at',
            'created_at',
            'updated_at',
        ];
    }
}
