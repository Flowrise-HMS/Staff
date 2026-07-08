<?php

namespace Modules\Staff\Classes\Fhir;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\FHIR\Contracts\FhirResourceContract;
use Modules\Staff\Models\StaffDepartment;

class FhirPractitionerRoleTransformer implements FhirResourceContract
{
    public function resourceType(): string
    {
        return 'PractitionerRole';
    }

    public function toFhir(Model $model): array
    {
        $staffDept = $model;
        $staff = $staffDept->staff;
        $department = $staffDept->department;

        $fhir = [
            'resourceType' => 'PractitionerRole',
            'id' => $staffDept->id,
            'practitioner' => [
                'reference' => "Practitioner/{$staff->id}",
            ],
            'organization' => [
                'reference' => "Organization/{$staff->branch->organization->id}",
            ],
            'location' => $this->buildLocations($department),
            'active' => $staffDept->is_active,
        ];

        if ($staffDept->start_date || $staffDept->end_date) {
            $period = [];
            if ($staffDept->start_date) {
                $period['start'] = $staffDept->start_date->format('Y-m-d');
            }
            if ($staffDept->end_date) {
                $period['end'] = $staffDept->end_date->format('Y-m-d');
            }
            $fhir['period'] = $period;
        }

        if ($staffDept->designation) {
            $fhir['code'] = [
                [
                    'coding' => [
                        [
                            'system' => 'https://flowrise.app/designations',
                            'code' => $staffDept->designation,
                        ],
                    ],
                    'text' => $this->formatDesignationText($staffDept->designation),
                ],
            ];
        }

        $specialties = $this->buildSpecialties($staff);
        if (! empty($specialties)) {
            $fhir['specialty'] = $specialties;
        }

        return $fhir;
    }

    public function fromFhir(array $fhirResource): array
    {
        $result = [];

        $practitioner = $fhirResource['practitioner'] ?? [];
        if (! empty($practitioner['reference'])) {
            $result['staff_id'] = str_replace('Practitioner/', '', $practitioner['reference']);
        }

        $code = $fhirResource['code'][0] ?? [];
        $coding = $code['coding'][0] ?? [];
        $result['designation'] = $coding['code'] ?? ($code['text'] ?? null);

        return $result;
    }

    public function findById(string $id): ?Model
    {
        return StaffDepartment::query()->find($id);
    }

    public function query(): Builder
    {
        return StaffDepartment::query();
    }

    public function searchableParameters(): array
    {
        return [
            '_id' => ['column' => 'id'],
            'practitioner' => ['column' => 'staff_id'],
            'organization' => ['column' => 'staff_id'],
            'active' => ['column' => 'end_date'],
            'specialty' => ['column' => 'staff_id'],
        ];
    }

    public function validateBusinessRules(array $fhirResource): array
    {
        $errors = [];

        $practitioner = $fhirResource['practitioner'] ?? [];
        if (empty($practitioner['reference'])) {
            $errors['practitioner'] = 'Practitioner reference is required.';
        }

        return $errors;
    }

    private function buildLocations(Model $department): array
    {
        $locations = $department->relationLoaded('locations')
            ? $department->locations
            : null;

        if (! $locations || $locations->isEmpty()) {
            return [];
        }

        $result = [];
        foreach ($locations as $location) {
            if ($location->is_active) {
                $result[] = [
                    'reference' => "Location/{$location->id}",
                ];
            }
        }

        return $result;
    }

    private function buildSpecialties(Model $staff): array
    {
        $specialties = $staff->relationLoaded('specialties')
            ? $staff->specialties
            : null;

        if (! $specialties || $specialties->isEmpty()) {
            return [];
        }

        $result = [];
        foreach ($specialties as $specialty) {
            $entry = [
                'coding' => [
                    [
                        'system' => 'https://flowrise.app/specialties',
                        'code' => $specialty->specialty_code ?? $specialty->specialty_name,
                    ],
                ],
                'text' => $specialty->specialty_name,
            ];

            $result[] = $entry;
        }

        return $result;
    }

    private function formatDesignationText(string $designation): string
    {
        return str_replace('_', ' ', ucwords($designation, '_'));
    }
}
