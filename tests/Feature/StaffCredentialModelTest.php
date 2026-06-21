<?php

namespace Modules\Staff\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Staff\Models\Staff;
use Modules\Staff\Models\StaffCredential;
use Tests\TestCase;

class StaffCredentialModelTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->migrateModules(['Core', 'Staff']);
    }

    public function test_staff_credential_factory_creates_credential(): void
    {
        $credential = StaffCredential::factory()->create();
        $this->assertTrue($credential->exists);
        $this->assertNotNull($credential->id);
    }

    public function test_staff_credential_belongs_to_staff(): void
    {
        $staff = Staff::factory()->create();
        $credential = StaffCredential::factory()->create(['staff_id' => $staff->id]);

        $this->assertTrue($credential->staff->is($staff));
    }

    public function test_staff_has_many_credentials(): void
    {
        $staff = Staff::factory()->create();
        $types = \Modules\Staff\Enums\CredentialType::cases();
        foreach (array_slice($types, 0, 3) as $type) {
            StaffCredential::factory()->create(['staff_id' => $staff->id, 'credential_type' => $type]);
        }

        $this->assertCount(3, $staff->fresh()->credentials);
    }
}
