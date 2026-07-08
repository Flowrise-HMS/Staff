<?php

namespace Modules\Staff\Classes\Fhir;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\FHIR\Contracts\FhirResourceContract;
use Modules\Staff\Enums\EmploymentStatus;
use Modules\Staff\Models\Staff;

class FhirPractitionerTransformer implements FhirResourceContract
{
    public function resourceType(): string
    {
        return 'Practitioner';
    }

    public function toFhir(Model $model): array
    {
        $staff = $model;

        $fhir = [
            'resourceType' => 'Practitioner',
            'id' => $staff->id,
            'identifier' => $this->buildIdentifiers($staff),
            'active' => $staff->employment_status === EmploymentStatus::ACTIVE,
            'name' => [$this->getNameEntry($staff)],
        ];

        if ($staff->gender) {
            $fhir['gender'] = $staff->gender->value;
        }

        if ($staff->date_of_birth) {
            $fhir['birthDate'] = $staff->date_of_birth->format('Y-m-d');
        }

        $qualifications = $this->buildQualifications($staff);
        if (! empty($qualifications)) {
            $fhir['qualification'] = $qualifications;
        }

        return $fhir;
    }

    public function fromFhir(array $fhirResource): array
    {
        $result = [];

        $name = $this->extractOfficialName($fhirResource['name'] ?? []);
        if ($name) {
            $given = $name['given'] ?? [];
            $result['first_name'] = $given[0] ?? '';
            $result['last_name'] = $name['family'] ?? '';
        }

        $result['gender'] = $fhirResource['gender'] ?? null;

        $result['_credentials'] = [];
        foreach ($fhirResource['qualification'] ?? [] as $qual) {
            $credential = [];

            $coding = $qual['code']['coding'][0] ?? [];
            $credential['credential_type'] = $coding['code'] ?? '';

            $identifier = $qual['identifier'][0] ?? [];
            $credential['credential_number'] = $identifier['value'] ?? '';

            $result['_credentials'][] = $credential;
        }

        return $result;
    }

    public function findById(string $id): ?Model
    {
        return Staff::withTrashed()->find($id);
    }

    public function query(): Builder
    {
        return Staff::query();
    }

    public function searchableParameters(): array
    {
        return [
            '_id' => ['column' => 'id'],
            'name' => ['column' => 'last_name'],
            'family' => ['column' => 'last_name'],
            'given' => ['column' => 'first_name'],
            'identifier' => ['column' => 'staff_number'],
            'active' => ['column' => 'employment_status'],
            'gender' => ['column' => 'gender'],
            'birthdate' => ['column' => 'date_of_birth'],
        ];
    }

    public function validateBusinessRules(array $fhirResource): array
    {
        $errors = [];

        $name = $this->extractOfficialName($fhirResource['name'] ?? []);
        if (! $name || empty($name['family'])) {
            $errors['family'] = 'Family name (last_name) is required.';
        }

        return $errors;
    }

    private function buildIdentifiers(Staff $staff): array
    {
        return [
            [
                'use' => 'official',
                'type' => [
                    'coding' => [
                        [
                            'system' => 'http://terminology.hl7.org/CodeSystem/v2-0203',
                            'code' => 'PRN',
                        ],
                    ],
                    'text' => 'Staff Number',
                ],
                'value' => $staff->staff_number,
            ],
        ];
    }

    private function getNameEntry(Staff $staff): array
    {
        $entry = [
            'use' => 'official',
            'family' => $staff->last_name,
        ];

        $given = array_filter([$staff->first_name]);
        if (! empty($given)) {
            $entry['given'] = array_values($given);
        }

        if ($staff->title) {
            $entry['prefix'] = [$staff->title instanceof \BackedEnum ? $staff->title->value : $staff->title];
        }

        return $entry;
    }

    private function buildQualifications(Staff $staff): array
    {
        $credentials = $staff->relationLoaded('credentials') ? $staff->credentials : null;
        if (! $credentials || $credentials->isEmpty()) {
            return [];
        }

        $qualifications = [];
        foreach ($credentials as $credential) {
            $qual = [
                'identifier' => [
                    ['value' => $credential->credential_number ?? ''],
                ],
                'code' => [
                    'coding' => [
                        [
                            'system' => 'https://flowrise.app/credential-types',
                            'code' => $credential->credential_type instanceof \BackedEnum
                                ? $credential->credential_type->value
                                : $credential->credential_type,
                            'display' => $credential->credential_type instanceof \BackedEnum
                                ? $credential->credential_type->getLabel()
                                : $credential->credential_type,
                        ],
                    ],
                    'text' => $credential->credential_type instanceof \BackedEnum
                        ? $credential->credential_type->getLabel()
                        : $credential->credential_type,
                ],
            ];

            if ($credential->issuing_authority) {
                $qual['issuer'] = ['display' => $credential->issuing_authority];
            }

            if ($credential->issue_date || $credential->expiry_date) {
                $period = [];
                if ($credential->issue_date) {
                    $period['start'] = $credential->issue_date->format('Y-m-d');
                }
                if ($credential->expiry_date) {
                    $period['end'] = $credential->expiry_date->format('Y-m-d');
                }
                $qual['period'] = $period;
            }

            $qualifications[] = $qual;
        }

        return $qualifications;
    }

    private function extractOfficialName(array $names): ?array
    {
        foreach ($names as $name) {
            if (($name['use'] ?? '') === 'official') {
                return $name;
            }
        }

        return $names[0] ?? null;
    }
}
