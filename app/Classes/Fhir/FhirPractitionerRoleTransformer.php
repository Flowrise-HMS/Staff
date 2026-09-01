<?php

namespace Modules\Staff\Classes\Fhir;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Core\Models\Department as DepartmentModel;
use Modules\FHIR\Contracts\FhirWritableResourceContract;
use Modules\Staff\Models\StaffDepartment;

class FhirPractitionerRoleTransformer implements FhirWritableResourceContract
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

        if (! empty($fhirResource['period']['start'])) {
            $result['start_date'] = $fhirResource['period']['start'];
        }

        if (! empty($fhirResource['period']['end'])) {
            $result['end_date'] = $fhirResource['period']['end'];
        }

        $departmentId = $this->resolveDepartmentId($fhirResource);
        if ($departmentId !== null) {
            $result['department_id'] = $departmentId;
        }

        return $result;
    }

    /**
     * Recover the department this role belongs to.
     *
     * The FHIR representation does not carry the department directly: `toFhir()`
     * maps `organization` to the branch's Organization and `location` to the
     * department's physical Locations. Department is therefore only reachable
     * backwards through a location — and since Location belongs to many
     * Departments, that inference is only sound when it lands on exactly one.
     *
     * Returns null when the answer is absent or ambiguous; validateBusinessRules()
     * turns that into a refusal rather than letting a write guess.
     *
     * @param  array<string, mixed>  $fhirResource
     */
    private function resolveDepartmentId(array $fhirResource): ?string
    {
        $locationIds = [];

        foreach ($fhirResource['location'] ?? [] as $location) {
            if (! empty($location['reference'])) {
                $locationIds[] = str_replace('Location/', '', $location['reference']);
            }
        }

        if ($locationIds === []) {
            return null;
        }

        $departmentIds = DepartmentModel::query()
            ->whereHas('locations', fn ($query) => $query->whereIn('locations.id', $locationIds))
            ->pluck('id')
            ->unique()
            ->all();

        return count($departmentIds) === 1 ? (string) $departmentIds[0] : null;
    }

    public function findById(string $id): ?Model
    {
        return StaffDepartment::query()->find($id);
    }

    /**
     * `toFhir()` walks six relations — staff, department, staff.branch,
     * branch.organization, department.locations and staff.specialties — so a bare
     * query issues roughly five extra statements per exported row.
     *
     * `staff.branch.organization` is also an unguarded chain: `staff.branch_id` and
     * `branches.organization_id` are both nullable, so eager loading them here is
     * what keeps a null from becoming a fatal mid-export.
     */
    public function query(): Builder
    {
        return StaffDepartment::query()->with([
            'staff.branch.organization',
            'staff.specialties',
            'department.locations',
        ]);
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

        /*
         * A role row is meaningless without its department, and the department is
         * only recoverable by walking back from `location`. Refuse rather than
         * guess: assigning a practitioner to the wrong department is a clinical
         * routing error, not a cosmetic one.
         */
        if ($this->resolveDepartmentId($fhirResource) === null) {
            $errors['location'] = 'Exactly one department must be resolvable from the supplied location references.';
        }

        return $errors;
    }

    public function createFromFhir(array $fhirResource): Model
    {
        return StaffDepartment::create($this->writableAttributes($fhirResource));
    }

    public function updateFromFhir(Model $model, array $fhirResource): Model
    {
        $model->update($this->writableAttributes($fhirResource));

        return $model->fresh();
    }

    /**
     * StaffDepartment is a pivot with no service of its own, so the write happens
     * through the model. It extends Pivot rather than BaseModel, so neither the
     * branch scope nor the audit traits apply — branch containment comes from the
     * staff and department references both having been resolved within the
     * caller's branch.
     *
     * @param  array<string, mixed>  $fhirResource
     * @return array<string, mixed>
     */
    private function writableAttributes(array $fhirResource): array
    {
        return array_filter(
            $this->fromFhir($fhirResource),
            static fn (mixed $value): bool => $value !== null && $value !== '',
        );
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
