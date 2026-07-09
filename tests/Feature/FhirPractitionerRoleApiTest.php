<?php

namespace Modules\Staff\Tests\Feature;

use Tests\TestCase;

class FhirPractitionerRoleApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->requireModule('FHIR');
    }

    public function test_search_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/fhir/PractitionerRole');

        $response->assertStatus(401);
    }

    public function test_read_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/fhir/PractitionerRole/non-existent');

        $response->assertStatus(401);
    }
}
