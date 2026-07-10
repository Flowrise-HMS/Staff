<?php

use Tests\TestCase;

uses(TestCase::class);

use Illuminate\Support\Collection;
use Modules\Core\Models\Branch;
use Modules\Core\Models\Department;
use Modules\Core\Models\Location;
use Modules\Core\Models\Organization;
use Modules\FHIR\Contracts\FhirResourceContract;
use Modules\Staff\Classes\Fhir\FhirPractitionerRoleTransformer;
use Modules\Staff\Models\Staff;
use Modules\Staff\Models\StaffDepartment;
use Modules\Staff\Models\StaffSpecialty;

$transformer = new FhirPractitionerRoleTransformer;

test('implements FhirResourceContract', function () use ($transformer) {
    expect($transformer)->toBeInstanceOf(FhirResourceContract::class);
});

test('resourceType returns PractitionerRole', function () use ($transformer) {
    expect($transformer->resourceType())->toBe('PractitionerRole');
});

test('toFhir contains required fields with relationship chain', function () use ($transformer) {
    $org = (new class extends Organization
    {
        public $timestamps = false;
    });
    $org->setAttribute('id', 'org-uuid');

    $branch = (new class extends Branch
    {
        public $timestamps = false;
    });
    $branch->setAttribute('id', 'branch-uuid');
    $branch->setRelation('organization', $org);

    $staff = (new class extends Staff
    {
        public $timestamps = false;
    });
    $staff->setAttribute('id', 'staff-uuid');
    $staff->setAttribute('staff_number', 'STF-001');
    $staff->setAttribute('first_name', 'Jane');
    $staff->setAttribute('last_name', 'Smith');
    $staff->setRelation('branch', $branch);

    $department = (new class extends Department
    {
        public $timestamps = false;
    });
    $department->setAttribute('id', 'dept-uuid');

    $staffDept = (new class extends StaffDepartment
    {
        public $timestamps = false;

        protected $table = 'staff_departments';
    });
    $staffDept->setAttribute('id', 'pr-uuid');
    $staffDept->setRelation('staff', $staff);
    $staffDept->setRelation('department', $department);

    $fhir = $transformer->toFhir($staffDept);

    expect($fhir)->toHaveKey('resourceType', 'PractitionerRole');
    expect($fhir)->toHaveKey('id', 'pr-uuid');
    expect($fhir['practitioner']['reference'])->toBe('Practitioner/staff-uuid');
    expect($fhir['organization']['reference'])->toBe('Organization/org-uuid');
    expect($fhir)->toHaveKey('active', true);
});

test('toFhir maps period with start and end dates', function () use ($transformer) {
    $org = (new class extends Organization
    {
        public $timestamps = false;
    });
    $org->setAttribute('id', 'org-uuid');

    $branch = (new class extends Branch
    {
        public $timestamps = false;
    });
    $branch->setAttribute('id', 'branch-uuid');
    $branch->setRelation('organization', $org);

    $staff = (new class extends Staff
    {
        public $timestamps = false;
    });
    $staff->setAttribute('id', 'staff-uuid');
    $staff->setRelation('branch', $branch);

    $department = (new class extends Department
    {
        public $timestamps = false;
    });
    $department->setAttribute('id', 'dept-uuid');

    $staffDept = (new class extends StaffDepartment
    {
        public $timestamps = false;

        protected $table = 'staff_departments';
    });
    $staffDept->setAttribute('id', 'pr-per-1');
    $staffDept->setAttribute('start_date', '2026-01-01');
    $staffDept->setAttribute('end_date', '2026-12-31');
    $staffDept->setRelation('staff', $staff);
    $staffDept->setRelation('department', $department);

    $fhir = $transformer->toFhir($staffDept);

    expect($fhir['period']['start'])->toBe('2026-01-01');
    expect($fhir['period']['end'])->toBe('2026-12-31');
});

test('toFhir omits period when no dates', function () use ($transformer) {
    $org = (new class extends Organization
    {
        public $timestamps = false;
    });
    $org->setAttribute('id', 'org-uuid');

    $branch = (new class extends Branch
    {
        public $timestamps = false;
    });
    $branch->setAttribute('id', 'branch-uuid');
    $branch->setRelation('organization', $org);

    $staff = (new class extends Staff
    {
        public $timestamps = false;
    });
    $staff->setAttribute('id', 'staff-uuid');
    $staff->setRelation('branch', $branch);

    $department = (new class extends Department
    {
        public $timestamps = false;
    });
    $department->setAttribute('id', 'dept-uuid');

    $staffDept = (new class extends StaffDepartment
    {
        public $timestamps = false;

        protected $table = 'staff_departments';
    });
    $staffDept->setAttribute('id', 'pr-noper-1');
    $staffDept->setRelation('staff', $staff);
    $staffDept->setRelation('department', $department);

    $fhir = $transformer->toFhir($staffDept);

    expect($fhir)->not->toHaveKey('period');
});

test('toFhir maps code from designation', function () use ($transformer) {
    $org = (new class extends Organization
    {
        public $timestamps = false;
    });
    $org->setAttribute('id', 'org-uuid');

    $branch = (new class extends Branch
    {
        public $timestamps = false;
    });
    $branch->setAttribute('id', 'branch-uuid');
    $branch->setRelation('organization', $org);

    $staff = (new class extends Staff
    {
        public $timestamps = false;
    });
    $staff->setAttribute('id', 'staff-uuid');
    $staff->setRelation('branch', $branch);

    $department = (new class extends Department
    {
        public $timestamps = false;
    });
    $department->setAttribute('id', 'dept-uuid');

    $staffDept = (new class extends StaffDepartment
    {
        public $timestamps = false;

        protected $table = 'staff_departments';
    });
    $staffDept->setAttribute('id', 'pr-code-1');
    $staffDept->setAttribute('designation', 'consultant_physician');
    $staffDept->setRelation('staff', $staff);
    $staffDept->setRelation('department', $department);

    $fhir = $transformer->toFhir($staffDept);

    expect($fhir['code'][0]['coding'][0]['system'])->toBe('https://flowrise.app/designations');
    expect($fhir['code'][0]['coding'][0]['code'])->toBe('consultant_physician');
    expect($fhir['code'][0]['text'])->toBe('Consultant Physician');
});

test('toFhir maps locations from department', function () use ($transformer) {
    $org = (new class extends Organization
    {
        public $timestamps = false;
    });
    $org->setAttribute('id', 'org-uuid');

    $branch = (new class extends Branch
    {
        public $timestamps = false;
    });
    $branch->setAttribute('id', 'branch-uuid');
    $branch->setRelation('organization', $org);

    $staff = (new class extends Staff
    {
        public $timestamps = false;
    });
    $staff->setAttribute('id', 'staff-uuid');
    $staff->setRelation('branch', $branch);

    $location = (new class extends Location
    {
        public $timestamps = false;
    });
    $location->setAttribute('id', 'loc-uuid');
    $location->setAttribute('is_active', true);

    $department = (new class extends Department
    {
        public $timestamps = false;
    });
    $department->setAttribute('id', 'dept-uuid');
    $department->setRelation('locations', new Collection([$location]));

    $staffDept = (new class extends StaffDepartment
    {
        public $timestamps = false;

        protected $table = 'staff_departments';
    });
    $staffDept->setAttribute('id', 'pr-loc-1');
    $staffDept->setRelation('staff', $staff);
    $staffDept->setRelation('department', $department);

    $fhir = $transformer->toFhir($staffDept);

    expect($fhir['location'][0]['reference'])->toBe('Location/loc-uuid');
});

test('toFhir maps specialties from staff', function () use ($transformer) {
    $org = (new class extends Organization
    {
        public $timestamps = false;
    });
    $org->setAttribute('id', 'org-uuid');

    $branch = (new class extends Branch
    {
        public $timestamps = false;
    });
    $branch->setAttribute('id', 'branch-uuid');
    $branch->setRelation('organization', $org);

    $specialty = (new class extends StaffSpecialty
    {
        public $timestamps = false;
    });
    $specialty->setAttribute('specialty_name', 'Cardiology');
    $specialty->setAttribute('specialty_code', 'CARD');

    $staff = (new class extends Staff
    {
        public $timestamps = false;
    });
    $staff->setAttribute('id', 'staff-uuid');
    $staff->setRelation('branch', $branch);
    $staff->setRelation('specialties', new Collection([$specialty]));

    $department = (new class extends Department
    {
        public $timestamps = false;
    });
    $department->setAttribute('id', 'dept-uuid');

    $staffDept = (new class extends StaffDepartment
    {
        public $timestamps = false;

        protected $table = 'staff_departments';
    });
    $staffDept->setAttribute('id', 'pr-spec-1');
    $staffDept->setRelation('staff', $staff);
    $staffDept->setRelation('department', $department);

    $fhir = $transformer->toFhir($staffDept);

    expect($fhir['specialty'][0]['coding'][0]['system'])->toBe('https://flowrise.app/specialties');
    expect($fhir['specialty'][0]['coding'][0]['code'])->toBe('CARD');
    expect($fhir['specialty'][0]['text'])->toBe('Cardiology');
});

test('fromFhir extracts staff_id and designation', function () use ($transformer) {
    $fhirResource = [
        'resourceType' => 'PractitionerRole',
        'practitioner' => ['reference' => 'Practitioner/staff-uuid'],
        'code' => [
            [
                'coding' => [
                    ['system' => 'https://flowrise.app/designations', 'code' => 'consultant_physician'],
                ],
            ],
        ],
    ];

    $attrs = $transformer->fromFhir($fhirResource);

    expect($attrs)->toHaveKey('staff_id', 'staff-uuid');
    expect($attrs)->toHaveKey('designation', 'consultant_physician');
});

test('fromFhir handles minimal resource', function () use ($transformer) {
    $fhirResource = ['resourceType' => 'PractitionerRole'];

    $attrs = $transformer->fromFhir($fhirResource);

    expect($attrs)->not->toHaveKey('staff_id');
    expect($attrs['designation'])->toBeNull();
});

test('searchableParameters has expected keys', function () use ($transformer) {
    $params = $transformer->searchableParameters();

    expect($params)->toHaveKeys(['_id', 'practitioner', 'organization', 'active', 'specialty']);
    expect($params['_id'])->toHaveKey('column', 'id');
    expect($params['practitioner'])->toHaveKey('column', 'staff_id');
});

test('validateBusinessRules passes with practitioner reference', function () use ($transformer) {
    $resource = ['resourceType' => 'PractitionerRole', 'practitioner' => ['reference' => 'Practitioner/uuid']];

    $errors = $transformer->validateBusinessRules($resource);

    expect($errors)->toBeEmpty();
});

test('validateBusinessRules fails without practitioner reference', function () use ($transformer) {
    $resource = ['resourceType' => 'PractitionerRole'];

    $errors = $transformer->validateBusinessRules($resource);

    expect($errors)->toHaveKey('practitioner');
});
