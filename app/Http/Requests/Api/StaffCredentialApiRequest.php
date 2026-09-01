<?php

namespace Modules\Staff\Http\Requests\Api;

use Modules\Staff\Http\Requests\StaffCredentialRequest;

/**
 * API flavour of {@see StaffCredentialRequest}.
 *
 * Two differences from the panel request:
 *
 *  - `staff_id` comes from the nested route, not the body. Accepting it from the
 *    body would let a caller attach a credential to a staff member they never
 *    passed authorization against.
 *  - the verification fields are not client-writable. Verifying a credential is a
 *    service operation (`StaffCredentialService::verify()`) that records who
 *    verified it and dispatches an event; letting a client PUT `verified_by`
 *    directly would forge that audit trail.
 */
class StaffCredentialApiRequest extends StaffCredentialRequest
{
    /**
     * Fields the panel may set but an API client may not.
     *
     * @var list<string>
     */
    private const WITHHELD = [
        'staff_id',
        'verified_by',
        'verified_at',
        'verification_notes',
        'rejection_reason',
        'document_path',
    ];

    public function rules(): array
    {
        return collect(parent::rules())
            ->except(self::WITHHELD)
            ->all();
    }

    protected function prepareForValidation(): void
    {
        $this->request->remove('staff_id');
    }

    /**
     * @return array<string, mixed>
     */
    public function attributesForStaff(): array
    {
        return collect($this->validated())->except(self::WITHHELD)->all();
    }
}
