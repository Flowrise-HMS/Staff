<?php

namespace Modules\Staff\Classes\Fhir;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\FHIR\Contracts\FhirWritableResourceContract;
use Modules\Staff\Classes\Services\StaffCredentialService;
use Modules\Staff\Classes\Services\StaffService;
use Modules\Staff\Enums\EmploymentStatus;
use Modules\Staff\Models\Staff;

class FhirPractitionerTransformer implements FhirWritableResourceContract
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

    public function createFromFhir(array $fhirResource): Model
    {
        [$attributes, $credentials] = $this->splitAttributes($fhirResource);

        $staff = app(StaffService::class)->create($attributes);

        $this->syncCredentials($staff, $credentials);

        return $staff->fresh();
    }

    public function updateFromFhir(Model $model, array $fhirResource): Model
    {
        /** @var Staff $model */
        [$attributes, $credentials] = $this->splitAttributes($fhirResource);

        $staff = app(StaffService::class)->update($model, $attributes);

        $this->syncCredentials($staff, $credentials);

        return $staff->fresh();
    }

    /**
     * Separate Staff columns from the qualification rows.
     *
     * `fromFhir()` emits `_credentials` for FHIR `qualification` entries, which are
     * StaffCredential records rather than columns on Staff and so must not reach
     * mass assignment.
     *
     * @param  array<string, mixed>  $fhirResource
     * @return array{0: array<string, mixed>, 1: list<array<string, mixed>>}
     */
    private function splitAttributes(array $fhirResource): array
    {
        $attributes = $this->fromFhir($fhirResource);

        $credentials = $attributes['_credentials'] ?? [];
        unset($attributes['_credentials']);

        $attributes = array_filter(
            $attributes,
            static fn (mixed $value): bool => $value !== null && $value !== '',
        );

        return [$attributes, $credentials];
    }

    /**
     * Qualifications are matched on credential number, which is unique. An unknown
     * number is added; a known one is left alone rather than overwritten, because
     * the verification state attached to it is recorded by staff and is not
     * something an inbound FHIR resource is entitled to reset.
     *
     * @param  list<array<string, mixed>>  $credentials
     */
    private function syncCredentials(Staff $staff, array $credentials): void
    {
        $service = app(StaffCredentialService::class);

        foreach ($credentials as $credential) {
            if (empty($credential['credential_number']) || empty($credential['credential_type'])) {
                continue;
            }

            $exists = $staff->credentials()
                ->where('credential_number', $credential['credential_number'])
                ->exists();

            if ($exists) {
                continue;
            }

            $service->create($staff, $credential);
        }
    }

    public function findById(string $id): ?Model
    {
        return Staff::withTrashed()->find($id);
    }

    /**
     * `toFhir()` reads `credentials` behind a relationLoaded() guard, so without
     * eager loading a bulk export emits every practitioner with no qualifications
     * at all — wrong data rather than slow data.
     */
    public function query(): Builder
    {
        return Staff::query()->with(['credentials']);
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
