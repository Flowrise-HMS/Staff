<?php

namespace Modules\Staff\Tests\Feature;

use Tests\TestCase;

class FhirPractitionerApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (!env('FHIR_INTEGRATION_TESTS', false)) {
            $this->markTestSkipped('Integration tests require FHIR_INTEGRATION_TESTS=true and database setup.');
        }
    }

    public function test_search_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/fhir/Practitioner');

        $response->assertStatus(401);
    }

    public function test_read_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/fhir/Practitioner/non-existent');

        $response->assertStatus(401);
    }
}
