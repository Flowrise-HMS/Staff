<?php

namespace Modules\Staff\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Staff\Enums\CredentialStatus;
use Modules\Staff\Enums\CredentialType;

class StaffCredentialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $credentialId = $this->route('credential')?->id;

        return [
            'staff_id' => ['required', 'uuid', 'exists:staff,id'],
            'credential_type' => ['required', Rule::enum(CredentialType::class)],
            'credential_number' => ['required', 'string', 'max:100', Rule::unique('staff_credentials', 'credential_number')->ignore($credentialId)],
            'issuing_authority' => ['required', 'string', 'max:255'],
            'issuing_country' => ['nullable', 'string', 'max:100'],
            'issuing_state' => ['nullable', 'string', 'max:100'],
            'issue_date' => ['nullable', 'date'],
            'expiry_date' => ['nullable', 'date', 'after:issue_date'],
            'status' => ['nullable', Rule::enum(CredentialStatus::class)],
            'verified_by' => ['nullable', 'integer', 'exists:users,id'],
            'verified_at' => ['nullable', 'date'],
            'verification_notes' => ['nullable', 'string', 'max:1000'],
            'rejection_reason' => ['nullable', 'string', 'max:1000'],
            'document_path' => ['nullable', 'string', 'max:500'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'staff_id.required' => 'Staff member is required.',
            'staff_id.exists' => 'Selected staff does not exist.',
            'credential_type.required' => 'Credential type is required.',
            'credential_number.required' => 'Credential number is required.',
            'credential_number.unique' => 'This credential number is already registered.',
            'issuing_authority.required' => 'Issuing authority is required.',
            'expiry_date.after' => 'Expiry date must be after the issue date.',
        ];
    }
}
