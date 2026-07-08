<?php

namespace Modules\Staff\Tests\Unit;

use Carbon\Carbon;
use Modules\Core\Enums\Title;
use Modules\FHIR\Contracts\FhirResourceContract;
use Modules\Patient\Enums\Gender;
use Modules\Staff\Classes\Fhir\FhirPractitionerTransformer;
use Modules\Staff\Enums\CredentialType;
use Modules\Staff\Enums\EmploymentStatus;
use Modules\Staff\Models\Staff;
use Modules\Staff\Models\StaffCredential;
use Tests\TestCase;

class FhirPractitionerTransformerTest extends TestCase
{
    private function createTransformer(): FhirResourceContract
    {
        return new FhirPractitionerTransformer;
    }

    private function createMinimalPractitioner(): Staff
    {
        $staff = new Staff;
        $staff->id = '550e8400-e29b-41d4-a716-446655440000';
        $staff->staff_number = 'STF-2025-00001';
        $staff->title = Title::DR;
        $staff->first_name = 'John';
        $staff->last_name = 'Smith';
        $staff->gender = Gender::MALE;
        $staff->date_of_birth = Carbon::parse('1985-06-15');
        $staff->employment_status = EmploymentStatus::ACTIVE;

        return $staff;
    }

    public function test_implements_contract(): void
    {
        $transformer = $this->createTransformer();

        $this->assertInstanceOf(FhirResourceContract::class, $transformer);
    }

    public function test_resource_type_returns_practitioner(): void
    {
        $transformer = $this->createTransformer();

        $this->assertEquals('Practitioner', $transformer->resourceType());
    }

    public function test_to_fhir_contains_required_fields(): void
    {
        $staff = $this->createMinimalPractitioner();
        $transformer = $this->createTransformer();

        $fhir = $transformer->toFhir($staff);

        $this->assertArrayHasKey('resourceType', $fhir);
        $this->assertArrayHasKey('id', $fhir);
        $this->assertArrayHasKey('identifier', $fhir);
        $this->assertArrayHasKey('name', $fhir);
        $this->assertArrayHasKey('gender', $fhir);
        $this->assertArrayHasKey('active', $fhir);
        $this->assertEquals('Practitioner', $fhir['resourceType']);
        $this->assertEquals($staff->id, $fhir['id']);
    }

    public function test_to_fhir_maps_name_with_title(): void
    {
        $staff = $this->createMinimalPractitioner();
        $staff->title = Title::DR;
        $transformer = $this->createTransformer();

        $fhir = $transformer->toFhir($staff);

        $name = $fhir['name'][0];
        $this->assertEquals('official', $name['use']);
        $this->assertEquals('Smith', $name['family']);
        $this->assertEquals(['John'], $name['given']);
        $this->assertEquals(['Dr'], $name['prefix']);
    }

    public function test_to_fhir_maps_staff_number_identifier(): void
    {
        $staff = $this->createMinimalPractitioner();
        $transformer = $this->createTransformer();

        $fhir = $transformer->toFhir($staff);

        $this->assertArrayHasKey('identifier', $fhir);
        $identifier = $fhir['identifier'][0];
        $this->assertEquals('official', $identifier['use']);
        $this->assertEquals('STF-2025-00001', $identifier['value']);
        $this->assertEquals('PRN', $identifier['type']['coding'][0]['code']);
        $this->assertEquals('Staff Number', $identifier['type']['text']);
    }

    public function test_to_fhir_maps_active_true_when_employment_status_active(): void
    {
        $staff = $this->createMinimalPractitioner();
        $staff->employment_status = EmploymentStatus::ACTIVE;
        $transformer = $this->createTransformer();

        $fhir = $transformer->toFhir($staff);

        $this->assertTrue($fhir['active']);
    }

    public function test_to_fhir_maps_active_false_when_not_active(): void
    {
        $staff = $this->createMinimalPractitioner();
        $staff->employment_status = EmploymentStatus::TERMINATED;
        $transformer = $this->createTransformer();

        $fhir = $transformer->toFhir($staff);

        $this->assertFalse($fhir['active']);
    }

    public function test_to_fhir_maps_gender(): void
    {
        $staff = $this->createMinimalPractitioner();
        $staff->gender = Gender::FEMALE;
        $transformer = $this->createTransformer();

        $fhir = $transformer->toFhir($staff);

        $this->assertEquals('female', $fhir['gender']);
    }

    public function test_to_fhir_maps_birth_date(): void
    {
        $staff = $this->createMinimalPractitioner();
        $staff->date_of_birth = Carbon::parse('1985-06-15');
        $transformer = $this->createTransformer();

        $fhir = $transformer->toFhir($staff);

        $this->assertEquals('1985-06-15', $fhir['birthDate']);
    }

    public function test_to_fhir_includes_qualifications(): void
    {
        $staff = $this->createMinimalPractitioner();

        $credential = new StaffCredential;
        $credential->credential_type = CredentialType::MEDICAL_LICENSE;
        $credential->credential_number = 'ML-12345';
        $credential->issuing_authority = 'Medical Board';
        $credential->issue_date = Carbon::parse('2020-01-01');
        $credential->expiry_date = Carbon::parse('2025-01-01');

        $staff->setRelation('credentials', collect([$credential]));
        $transformer = $this->createTransformer();

        $fhir = $transformer->toFhir($staff);

        $this->assertArrayHasKey('qualification', $fhir);
        $this->assertCount(1, $fhir['qualification']);
        $qual = $fhir['qualification'][0];
        $this->assertEquals('ML-12345', $qual['identifier'][0]['value']);
        $this->assertEquals('medical_license', $qual['code']['coding'][0]['code']);
        $this->assertEquals('Medical License', $qual['code']['coding'][0]['display']);
        $this->assertEquals('Medical Board', $qual['issuer']['display']);
        $this->assertEquals('2020-01-01', $qual['period']['start']);
        $this->assertEquals('2025-01-01', $qual['period']['end']);
    }

    public function test_to_fhir_skips_qualifications_when_no_credentials(): void
    {
        $staff = $this->createMinimalPractitioner();
        $staff->setRelation('credentials', collect([]));
        $transformer = $this->createTransformer();

        $fhir = $transformer->toFhir($staff);

        $this->assertArrayNotHasKey('qualification', $fhir);
    }

    public function test_from_fhir_returns_correct_structure(): void
    {
        $transformer = $this->createTransformer();

        $fhirResource = [
            'resourceType' => 'Practitioner',
            'id' => 'ext-uuid',
            'name' => [
                [
                    'use' => 'official',
                    'family' => 'Smith',
                    'given' => ['John'],
                    'prefix' => ['Dr'],
                ],
            ],
            'gender' => 'male',
            'birthDate' => '1985-06-15',
            'identifier' => [
                [
                    'use' => 'official',
                    'type' => [
                        'coding' => [['system' => 'http://terminology.hl7.org/CodeSystem/v2-0203', 'code' => 'PRN']],
                        'text' => 'Staff Number',
                    ],
                    'value' => 'STF-2025-00001',
                ],
            ],
            'qualification' => [
                [
                    'identifier' => [['value' => 'ML-12345']],
                    'code' => [
                        'coding' => [['system' => 'https://flowrise.app/credential-types', 'code' => 'medical_license']],
                    ],
                    'issuer' => ['display' => 'Medical Board'],
                    'period' => ['start' => '2020-01-01', 'end' => '2025-01-01'],
                ],
            ],
        ];

        $result = $transformer->fromFhir($fhirResource);

        $this->assertArrayHasKey('first_name', $result);
        $this->assertArrayHasKey('last_name', $result);
        $this->assertArrayHasKey('gender', $result);
        $this->assertArrayHasKey('_credentials', $result);
        $this->assertEquals('John', $result['first_name']);
        $this->assertEquals('Smith', $result['last_name']);
        $this->assertEquals('male', $result['gender']);
    }

    public function test_from_fhir_maps_credentials(): void
    {
        $transformer = $this->createTransformer();

        $fhirResource = [
            'resourceType' => 'Practitioner',
            'name' => [['family' => 'Smith', 'given' => ['John']]],
            'gender' => 'male',
            'qualification' => [
                [
                    'identifier' => [['value' => 'ML-12345']],
                    'code' => [
                        'coding' => [['code' => 'medical_license']],
                    ],
                ],
                [
                    'identifier' => [['value' => 'NPI-98765']],
                    'code' => [
                        'coding' => [['code' => 'npi_number']],
                    ],
                ],
            ],
        ];

        $result = $transformer->fromFhir($fhirResource);

        $this->assertCount(2, $result['_credentials']);
        $this->assertEquals('medical_license', $result['_credentials'][0]['credential_type']);
        $this->assertEquals('ML-12345', $result['_credentials'][0]['credential_number']);
        $this->assertEquals('npi_number', $result['_credentials'][1]['credential_type']);
        $this->assertEquals('NPI-98765', $result['_credentials'][1]['credential_number']);
    }

    public function test_from_fhir_handles_missing_qualifications(): void
    {
        $transformer = $this->createTransformer();

        $fhirResource = [
            'resourceType' => 'Practitioner',
            'name' => [['family' => 'Smith', 'given' => ['John']]],
            'gender' => 'male',
        ];

        $result = $transformer->fromFhir($fhirResource);

        $this->assertArrayHasKey('_credentials', $result);
        $this->assertEmpty($result['_credentials']);
    }

    public function test_findById_uses_staff_model(): void
    {
        $transformer = $this->createTransformer();

        $result = $transformer->findById('non-existent-id');

        $this->assertNull($result);
    }

    public function test_query_returns_builder(): void
    {
        $transformer = $this->createTransformer();

        $query = $transformer->query();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Builder::class, $query);
    }

    public function test_searchable_parameters_contains_expected_keys(): void
    {
        $transformer = $this->createTransformer();

        $params = $transformer->searchableParameters();

        $this->assertArrayHasKey('_id', $params);
        $this->assertArrayHasKey('name', $params);
        $this->assertArrayHasKey('family', $params);
        $this->assertArrayHasKey('given', $params);
        $this->assertArrayHasKey('identifier', $params);
        $this->assertArrayHasKey('active', $params);
        $this->assertArrayHasKey('gender', $params);
        $this->assertArrayHasKey('birthdate', $params);
    }

    public function test_searchable_parameters_has_correct_column_mappings(): void
    {
        $transformer = $this->createTransformer();

        $params = $transformer->searchableParameters();

        $this->assertEquals(['column' => 'id'], $params['_id']);
        $this->assertEquals(['column' => 'last_name'], $params['name']);
        $this->assertEquals(['column' => 'last_name'], $params['family']);
        $this->assertEquals(['column' => 'first_name'], $params['given']);
        $this->assertEquals(['column' => 'staff_number'], $params['identifier']);
        $this->assertEquals(['column' => 'employment_status'], $params['active']);
        $this->assertEquals(['column' => 'gender'], $params['gender']);
        $this->assertEquals(['column' => 'date_of_birth'], $params['birthdate']);
    }

    public function test_validate_business_rules_checks_family_name(): void
    {
        $transformer = $this->createTransformer();

        $resource = [
            'resourceType' => 'Practitioner',
            'name' => [
                ['given' => ['John']],
            ],
        ];

        $errors = $transformer->validateBusinessRules($resource);

        $this->assertNotEmpty($errors);
        $this->assertArrayHasKey('family', $errors);
    }

    public function test_validate_business_rules_passes_valid_resource(): void
    {
        $transformer = $this->createTransformer();

        $resource = [
            'resourceType' => 'Practitioner',
            'name' => [
                ['family' => 'Smith', 'given' => ['John']],
            ],
            'gender' => 'male',
        ];

        $errors = $transformer->validateBusinessRules($resource);

        $this->assertEmpty($errors);
    }
}
