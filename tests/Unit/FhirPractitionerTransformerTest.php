<?php

use Tests\TestCase;

uses(TestCase::class);

use Illuminate\Support\Collection;
use Modules\FHIR\Contracts\FhirResourceContract;
use Modules\Patient\Enums\Gender;
use Modules\Staff\Classes\Fhir\FhirPractitionerTransformer;
use Modules\Staff\Enums\CredentialType;
use Modules\Staff\Enums\EmploymentStatus;
use Modules\Staff\Models\Staff;
use Modules\Staff\Models\StaffCredential;

$transformer = new FhirPractitionerTransformer;

test('implements FhirResourceContract', function () use ($transformer) {
    expect($transformer)->toBeInstanceOf(FhirResourceContract::class);
});

test('resourceType returns Practitioner', function () use ($transformer) {
    expect($transformer->resourceType())->toBe('Practitioner');
});

test('toFhir contains required fields', function () use ($transformer) {
    $staff = (new class extends Staff
    {
        public $timestamps = false;
    });
    $staff->setAttribute('id', 'stf-0001');
    $staff->setAttribute('staff_number', 'STF-2026-00001');
    $staff->setAttribute('first_name', 'Jane');
    $staff->setAttribute('last_name', 'Smith');
    $staff->setAttribute('employment_status', EmploymentStatus::ACTIVE);

    $fhir = $transformer->toFhir($staff);

    expect($fhir)->toHaveKey('resourceType', 'Practitioner');
    expect($fhir)->toHaveKey('id', 'stf-0001');
    expect($fhir['identifier'][0]['use'])->toBe('official');
    expect($fhir['identifier'][0]['type']['coding'][0]['code'])->toBe('PRN');
    expect($fhir['identifier'][0]['value'])->toBe('STF-2026-00001');
    expect($fhir)->toHaveKey('active', true);
    expect($fhir['name'][0]['use'])->toBe('official');
    expect($fhir['name'][0]['family'])->toBe('Smith');
    expect($fhir['name'][0]['given'])->toBe(['Jane']);
});

test('toFhir maps active correctly based on employment status', function () use ($transformer) {
    $staff = (new class extends Staff
    {
        public $timestamps = false;
    });
    $staff->setAttribute('id', 'stf-act-1');
    $staff->setAttribute('staff_number', 'STF-ACT-1');
    $staff->setAttribute('first_name', 'Test');
    $staff->setAttribute('last_name', 'User');
    $staff->setAttribute('employment_status', EmploymentStatus::TERMINATED);

    $fhir = $transformer->toFhir($staff);

    expect($fhir['active'])->toBeFalse();
});

test('toFhir maps gender', function () use ($transformer) {
    $staff = (new class extends Staff
    {
        public $timestamps = false;
    });
    $staff->setAttribute('id', 'stf-gen-1');
    $staff->setAttribute('staff_number', 'STF-GEN-1');
    $staff->setAttribute('first_name', 'Test');
    $staff->setAttribute('last_name', 'User');
    $staff->setAttribute('employment_status', EmploymentStatus::ACTIVE);
    $staff->setAttribute('gender', Gender::FEMALE);

    $fhir = $transformer->toFhir($staff);

    expect($fhir['gender'])->toBe('female');
});

test('toFhir omits gender when not set', function () use ($transformer) {
    $staff = (new class extends Staff
    {
        public $timestamps = false;
    });
    $staff->setAttribute('id', 'stf-nogen-1');
    $staff->setAttribute('staff_number', 'STF-NOGEN-1');
    $staff->setAttribute('first_name', 'Test');
    $staff->setAttribute('last_name', 'User');
    $staff->setAttribute('employment_status', EmploymentStatus::ACTIVE);

    $fhir = $transformer->toFhir($staff);

    expect($fhir)->not->toHaveKey('gender');
});

test('toFhir maps qualification from credentials', function () use ($transformer) {
    $credential = (new class extends StaffCredential
    {
        public $timestamps = false;
    });
    $credential->setAttribute('credential_type', CredentialType::MEDICAL_LICENSE);
    $credential->setAttribute('credential_number', 'ML-12345');
    $credential->setAttribute('issuing_authority', 'Medical Board');
    $credential->setAttribute('issue_date', '2025-01-01');
    $credential->setAttribute('expiry_date', '2028-01-01');

    $staff = (new class extends Staff
    {
        public $timestamps = false;
    });
    $staff->setAttribute('id', 'stf-qual-1');
    $staff->setAttribute('staff_number', 'STF-QUAL-1');
    $staff->setAttribute('first_name', 'Test');
    $staff->setAttribute('last_name', 'User');
    $staff->setAttribute('employment_status', EmploymentStatus::ACTIVE);
    $staff->setRelation('credentials', new Collection([$credential]));

    $fhir = $transformer->toFhir($staff);

    expect($fhir['qualification'][0]['identifier'][0]['value'])->toBe('ML-12345');
    expect($fhir['qualification'][0]['code']['coding'][0]['system'])->toBe('https://flowrise.app/credential-types');
    expect($fhir['qualification'][0]['code']['coding'][0]['code'])->toBe('medical_license');
    expect($fhir['qualification'][0]['code']['coding'][0]['display'])->toBe('Medical License');
    expect($fhir['qualification'][0]['issuer']['display'])->toBe('Medical Board');
    expect($fhir['qualification'][0]['period']['start'])->toBe('2025-01-01');
    expect($fhir['qualification'][0]['period']['end'])->toBe('2028-01-01');
});

test('toFhir omits qualification when no credentials loaded', function () use ($transformer) {
    $staff = (new class extends Staff
    {
        public $timestamps = false;
    });
    $staff->setAttribute('id', 'stf-noqual-1');
    $staff->setAttribute('staff_number', 'STF-NOQUAL-1');
    $staff->setAttribute('first_name', 'Test');
    $staff->setAttribute('last_name', 'User');
    $staff->setAttribute('employment_status', EmploymentStatus::ACTIVE);

    $fhir = $transformer->toFhir($staff);

    expect($fhir)->not->toHaveKey('qualification');
});

test('toFhir maps name with prefix from title', function () use ($transformer) {
    $staff = (new class extends Staff
    {
        public $timestamps = false;
    });
    $staff->setAttribute('id', 'stf-pref-1');
    $staff->setAttribute('staff_number', 'STF-PREF-1');
    $staff->setAttribute('first_name', 'John');
    $staff->setAttribute('last_name', 'Doe');
    $staff->setAttribute('title', 'Dr');
    $staff->setAttribute('employment_status', EmploymentStatus::ACTIVE);

    $fhir = $transformer->toFhir($staff);

    expect($fhir['name'][0]['prefix'])->toBe(['Dr']);
});

test('fromFhir extracts name and gender', function () use ($transformer) {
    $fhirResource = [
        'resourceType' => 'Practitioner',
        'name' => [
            [
                'use' => 'official',
                'family' => 'Smith',
                'given' => ['Jane'],
            ],
        ],
        'gender' => 'female',
    ];

    $attrs = $transformer->fromFhir($fhirResource);

    expect($attrs)->toHaveKey('first_name', 'Jane');
    expect($attrs)->toHaveKey('last_name', 'Smith');
    expect($attrs)->toHaveKey('gender', 'female');
});

test('fromFhir extracts _credentials from qualification', function () use ($transformer) {
    $fhirResource = [
        'resourceType' => 'Practitioner',
        'name' => [
            [
                'use' => 'official',
                'family' => 'Smith',
                'given' => ['Jane'],
            ],
        ],
        'qualification' => [
            [
                'identifier' => [
                    ['value' => 'ML-12345'],
                ],
                'code' => [
                    'coding' => [
                        ['system' => 'https://flowrise.app/credential-types', 'code' => 'medical_license'],
                    ],
                ],
            ],
        ],
    ];

    $attrs = $transformer->fromFhir($fhirResource);

    expect($attrs['_credentials'][0]['credential_type'])->toBe('medical_license');
    expect($attrs['_credentials'][0]['credential_number'])->toBe('ML-12345');
});

test('fromFhir handles minimal resource', function () use ($transformer) {
    $fhirResource = ['resourceType' => 'Practitioner'];

    $attrs = $transformer->fromFhir($fhirResource);

    expect($attrs)->toHaveKey('gender');
    expect($attrs)->toHaveKey('_credentials', []);
    expect($attrs)->not->toHaveKey('first_name');
    expect($attrs)->not->toHaveKey('last_name');
});

test('searchableParameters has expected keys', function () use ($transformer) {
    $params = $transformer->searchableParameters();

    expect($params)->toHaveKeys(['_id', 'name', 'family', 'given', 'identifier', 'active', 'gender', 'birthdate']);
    expect($params['_id'])->toHaveKey('column', 'id');
    expect($params['identifier'])->toHaveKey('column', 'staff_number');
    expect($params['active'])->toHaveKey('column', 'employment_status');
});

test('validateBusinessRules passes with valid data', function () use ($transformer) {
    $resource = ['resourceType' => 'Practitioner', 'name' => [['use' => 'official', 'family' => 'Smith']]];

    $errors = $transformer->validateBusinessRules($resource);

    expect($errors)->toBeEmpty();
});

test('validateBusinessRules fails without family name', function () use ($transformer) {
    $resource = ['resourceType' => 'Practitioner', 'name' => [['use' => 'official', 'given' => ['Jane']]]];

    $errors = $transformer->validateBusinessRules($resource);

    expect($errors)->toHaveKey('family');
});
