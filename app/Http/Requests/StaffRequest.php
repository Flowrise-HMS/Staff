<?php

namespace Modules\Staff\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Core\Enums\Title;
use Modules\Patient\Enums\Gender;
use Modules\Staff\Enums\EmploymentStatus;
use Modules\Staff\Enums\StaffType;

class StaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $staffId = $this->route('staff')?->id;

        return [
            'branch_id' => ['required', 'uuid', 'exists:branches,id'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'staff_number' => ['nullable', 'string', 'max:50', Rule::unique('staff', 'staff_number')->ignore($staffId)],
            'title' => ['nullable', Rule::enum(Title::class)],
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'date_of_birth' => ['nullable', 'date', 'before_or_equal:today'],
            'gender' => ['nullable', Rule::enum(Gender::class)],
            'staff_type' => ['nullable', Rule::enum(StaffType::class)],
            'employment_status' => ['nullable', Rule::enum(EmploymentStatus::class)],
            'hire_date' => ['nullable', 'date'],
            'termination_date' => ['nullable', 'date', 'after_or_equal:hire_date'],
            'termination_reason' => ['nullable', 'string', 'max:1000'],
            'address' => ['nullable', 'array'],
            'address.street' => ['nullable', 'string', 'max:255'],
            'address.city' => ['nullable', 'string', 'max:100'],
            'address.region' => ['nullable', 'string', 'max:100'],
            'address.country' => ['nullable', 'string', 'max:100'],
            'address.postal_code' => ['nullable', 'string', 'max:20'],
            'contact' => ['nullable', 'array'],
            'contact.phone' => ['nullable', 'string', 'max:50'],
            'contact.email' => ['nullable', 'email', 'max:255'],
            'emergency_contact' => ['nullable', 'array'],
            'emergency_contact.name' => ['nullable', 'string', 'max:255'],
            'emergency_contact.phone' => ['nullable', 'string', 'max:50'],
            'emergency_contact.relationship' => ['nullable', 'string', 'max:100'],
            'department_ids' => ['nullable', 'array'],
            'department_ids.*' => ['uuid', 'exists:departments,id'],
            'specialty_ids' => ['nullable', 'array'],
            'specialty_ids.*' => ['uuid', 'exists:staff_specialties,id'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'branch_id.required' => 'Branch is required.',
            'branch_id.exists' => 'Selected branch does not exist.',
            'first_name.required' => 'First name is required.',
            'last_name.required' => 'Last name is required.',
            'staff_number.unique' => 'This staff number is already assigned.',
            'termination_date.after_or_equal' => 'Termination date cannot be before hire date.',
        ];
    }
}
