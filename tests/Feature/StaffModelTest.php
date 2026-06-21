<?php

namespace Modules\Staff\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Core\Models\Branch;
use Modules\Staff\Models\Staff;
use Modules\Staff\Models\StaffSpecialty;
use Tests\TestCase;

class StaffModelTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->migrateModules(['Core', 'Staff']);
    }

    public function test_staff_factory_creates_staff(): void
    {
        $staff = Staff::factory()->create();
        $this->assertTrue($staff->exists);
        $this->assertNotNull($staff->id);
    }

    public function test_staff_belongs_to_branch(): void
    {
        $branch = Branch::factory()->create();
        $staff = Staff::factory()->create(['branch_id' => $branch->id]);

        $this->assertInstanceOf(Branch::class, $staff->branch);
        $this->assertEquals($branch->id, $staff->branch->id);
    }

    public function test_staff_has_specialties(): void
    {
        $staff = Staff::factory()->create();
        StaffSpecialty::factory()->count(2)->create(['staff_id' => $staff->id]);

        $this->assertCount(2, $staff->specialties);
    }

    public function test_staff_specialty_factory(): void
    {
        $specialty = StaffSpecialty::factory()->create();
        $this->assertTrue($specialty->exists);
    }
}
