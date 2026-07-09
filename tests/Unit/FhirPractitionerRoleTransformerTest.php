<?php

namespace Modules\Staff\Tests\Unit;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Modules\Core\Models\Branch;
use Modules\Core\Models\Department;
use Modules\Core\Models\Location;
use Modules\Core\Models\Organization;
use Modules\FHIR\Contracts\FhirResourceContract;
use Modules\Staff\Classes\Fhir\FhirPractitionerRoleTransformer;
use Modules\Staff\Models\Staff;
use Modules\Staff\Models\StaffDepartment;
use Modules\Staff\Models\StaffSpecialty;
use Tests\TestCase;

class FhirPractitionerRoleTransformerTest extends TestCase
{
    private function createTransformer(): FhirResourceContract
    {
        return new FhirPractitionerRoleTransformer;
    }

    private function createMinimalStaffDepartment(): StaffDepartment
    {
        $org = new Organization;
        $org->id = 'org-uuid-1';

        $branch = new Branch;
        $branch->id = 'branch-uuid-1';
        $branch->setRelation('organization', $org);

        $location1 = new Location;
        $location1->id = 'loc-uuid-1';
        $location1->is_active = true;

        $location2 = new Location;
        $location2->id = 'loc-uuid-2';
        $location2->is_active = true;

        $department = new Department;
        $department->id = 'dept-uuid-1';
        $department->name = 'Cardiology';
        $department->setRelation('locations', new Collection([$location1, $location2]));

        $specialty = new StaffSpecialty;
        $specialty->specialty_name = 'Cardiology';
        $specialty->specialty_code = 'CARD';
        $specialty->is_primary = true;

        $staff = new Staff;
        $staff->id = 'staff-uuid-1';
        $staff->branch_id = 'branch-uuid-1';
        $staff->setRelation('branch', $branch);
        $staff->setRelation('specialties', new Collection([$specialty]));

        $staffDept = new StaffDepartment;
        $staffDept->id = 'staff-dept-uuid-1';
        $staffDept->staff_id = 'staff-uuid-1';
        $staffDept->department_id = 'dept-uuid-1';
        $staffDept->is_primary = true;
        $staffDept->start_date = Carbon::parse('2023-01-01');
        $staffDept->end_date = Carbon::parse('2024-12-31');
        $staffDept->designation = 'nurse_practitioner';
        $staffDept->setRelation('staff', $staff);
        $staffDept->setRelation('department', $department);

        return $staffDept;
    }

    public function test_implements_contract(): void
    {
        $transformer = $this->createTransformer();

        $this->assertInstanceOf(FhirResourceContract::class, $transformer);
    }

    public function test_resource_type_returns_practitioner_role(): void
    {
        $transformer = $this->createTransformer();

        $this->assertEquals('PractitionerRole', $transformer->resourceType());
    }

    public function test_to_fhir_contains_required_fields(): void
    {
        $staffDept = $this->createMinimalStaffDepartment();
        $transformer = $this->createTransformer();

        $fhir = $transformer->toFhir($staffDept);

        $this->assertArrayHasKey('resourceType', $fhir);
        $this->assertArrayHasKey('id', $fhir);
        $this->assertArrayHasKey('practitioner', $fhir);
        $this->assertArrayHasKey('organization', $fhir);
        $this->assertArrayHasKey('location', $fhir);
        $this->assertEquals('PractitionerRole', $fhir['resourceType']);
        $this->assertEquals('staff-dept-uuid-1', $fhir['id']);
    }

    public function test_to_fhir_maps_practitioner_reference(): void
    {
        $staffDept = $this->createMinimalStaffDepartment();
        $transformer = $this->createTransformer();

        $fhir = $transformer->toFhir($staffDept);

        $this->assertEquals('Practitioner/staff-uuid-1', $fhir['practitioner']['reference']);
    }

    public function test_to_fhir_maps_organization_reference(): void
    {
        $staffDept = $this->createMinimalStaffDepartment();
        $transformer = $this->createTransformer();

        $fhir = $transformer->toFhir($staffDept);

        $this->assertEquals('Organization/org-uuid-1', $fhir['organization']['reference']);
    }

    public function test_to_fhir_maps_locations(): void
    {
        $staffDept = $this->createMinimalStaffDepartment();
        $transformer = $this->createTransformer();

        $fhir = $transformer->toFhir($staffDept);

        $this->assertCount(2, $fhir['location']);
        $this->assertEquals('Location/loc-uuid-1', $fhir['location'][0]['reference']);
        $this->assertEquals('Location/loc-uuid-2', $fhir['location'][1]['reference']);
    }

    public function test_to_fhir_maps_period(): void
    {
        $staffDept = $this->createMinimalStaffDepartment();
        $transformer = $this->createTransformer();

        $fhir = $transformer->toFhir($staffDept);

        $this->assertArrayHasKey('period', $fhir);
        $this->assertEquals('2023-01-01', $fhir['period']['start']);
        $this->assertEquals('2024-12-31', $fhir['period']['end']);
    }

    public function test_to_fhir_maps_active(): void
    {
        $staffDept = $this->createMinimalStaffDepartment();
        $staffDept->end_date = null;
        $transformer = $this->createTransformer();

        $fhir = $transformer->toFhir($staffDept);

        $this->assertTrue($fhir['active']);
    }

    public function test_to_fhir_maps_code_from_designation(): void
    {
        $staffDept = $this->createMinimalStaffDepartment();
        $staffDept->designation = 'nurse_practitioner';
        $transformer = $this->createTransformer();

        $fhir = $transformer->toFhir($staffDept);

        $this->assertCount(1, $fhir['code']);
        $this->assertEquals('nurse_practitioner', $fhir['code'][0]['coding'][0]['code']);
        $this->assertEquals('Nurse Practitioner', $fhir['code'][0]['text']);
    }

    public function test_to_fhir_omits_code_when_no_designation(): void
    {
        $staffDept = $this->createMinimalStaffDepartment();
        $staffDept->designation = null;
        $transformer = $this->createTransformer();

        $fhir = $transformer->toFhir($staffDept);

        $this->assertArrayNotHasKey('code', $fhir);
    }

    public function test_to_fhir_maps_specialties(): void
    {
        $staffDept = $this->createMinimalStaffDepartment();
        $transformer = $this->createTransformer();

        $fhir = $transformer->toFhir($staffDept);

        $this->assertCount(1, $fhir['specialty']);
        $this->assertEquals('Cardiology', $fhir['specialty'][0]['text']);
        $this->assertEquals('CARD', $fhir['specialty'][0]['coding'][0]['code']);
    }

    public function test_to_fhir_filters_inactive_locations(): void
    {
        $staffDept = $this->createMinimalStaffDepartment();

        $locationInactive = new Location;
        $locationInactive->id = 'loc-uuid-3';
        $locationInactive->is_active = false;

        $staffDept->department->setRelation('locations', new Collection([
            $staffDept->department->locations->first(),
            $locationInactive,
        ]));
        $transformer = $this->createTransformer();

        $fhir = $transformer->toFhir($staffDept);

        $this->assertCount(1, $fhir['location']);
        $this->assertEquals('Location/loc-uuid-1', $fhir['location'][0]['reference']);
    }

    public function test_query_returns_builder(): void
    {
        $transformer = $this->createTransformer();

        $query = $transformer->query();

        $this->assertInstanceOf(Builder::class, $query);
    }

    public function test_searchable_parameters_contains_expected_keys(): void
    {
        $transformer = $this->createTransformer();

        $params = $transformer->searchableParameters();

        $this->assertArrayHasKey('_id', $params);
        $this->assertArrayHasKey('practitioner', $params);
        $this->assertArrayHasKey('organization', $params);
        $this->assertArrayHasKey('active', $params);
        $this->assertArrayHasKey('specialty', $params);
    }

    public function test_searchable_parameters_has_correct_column_mappings(): void
    {
        $transformer = $this->createTransformer();

        $params = $transformer->searchableParameters();

        $this->assertEquals(['column' => 'id'], $params['_id']);
        $this->assertEquals(['column' => 'staff_id'], $params['practitioner']);
        $this->assertEquals(['column' => 'staff_id'], $params['organization']);
        $this->assertEquals(['column' => 'end_date'], $params['active']);
        $this->assertEquals(['column' => 'staff_id'], $params['specialty']);
    }

    public function test_validate_business_rules_checks_practitioner_reference(): void
    {
        $transformer = $this->createTransformer();

        $resource = [
            'resourceType' => 'PractitionerRole',
        ];

        $errors = $transformer->validateBusinessRules($resource);

        $this->assertNotEmpty($errors);
        $this->assertArrayHasKey('practitioner', $errors);
    }

    public function test_validate_business_rules_passes_valid_resource(): void
    {
        $transformer = $this->createTransformer();

        $resource = [
            'resourceType' => 'PractitionerRole',
            'practitioner' => ['reference' => 'Practitioner/550e8400-e29b-41d4-a716-446655440000'],
        ];

        $errors = $transformer->validateBusinessRules($resource);

        $this->assertEmpty($errors);
    }
}
