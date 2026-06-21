<?php

namespace Modules\Staff\Tests\Unit;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Core\Models\Department;
use Modules\Staff\Classes\Services\StaffAssignmentService;
use Modules\Staff\Models\Staff;
use Tests\TestCase;

class StaffAssignmentServiceTest extends TestCase
{
    use DatabaseTransactions;

    protected StaffAssignmentService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->migrateModules(['Core', 'Staff']);
        $this->service = new StaffAssignmentService;
    }

    public function test_can_assign_to_department(): void
    {
        $staff = Staff::factory()->create();
        $department = Department::factory()->create();

        $assignment = $this->service->assignToDepartment($staff, $department, true);

        $this->assertDatabaseHas('staff_departments', [
            'staff_id' => $staff->id,
            'department_id' => $department->id,
            'is_primary' => true,
        ]);
    }

    public function test_can_remove_from_department(): void
    {
        $staff = Staff::factory()->create();
        $department = Department::factory()->create();
        $staff->assignDepartment($department);

        $result = $this->service->removeFromDepartment($staff, $department);

        $this->assertTrue($result);
        $this->assertDatabaseHas('staff_departments', [
            'staff_id' => $staff->id,
            'department_id' => $department->id,
        ]);
        $this->assertNotNull($staff->fresh()->staffDepartments()->where('department_id', $department->id)->first()->end_date);
    }

    public function test_setting_primary_clears_existing_primary(): void
    {
        $staff = Staff::factory()->create();
        $dept1 = Department::factory()->create();
        $dept2 = Department::factory()->create();

        $this->service->assignToDepartment($staff, $dept1, true);
        $this->service->assignToDepartment($staff, $dept2, true);

        $this->assertEquals(1, $staff->staffDepartments()->where('is_primary', true)->count());
    }

    public function test_can_add_specialty(): void
    {
        $staff = Staff::factory()->create();

        $specialty = $this->service->addSpecialty(
            $staff,
            'Cardiology',
            'CARD',
            now()->subYears(2),
            now()->addYears(3),
            'Ghana Medical Council'
        );

        $this->assertDatabaseHas('staff_specialties', [
            'staff_id' => $staff->id,
            'specialty_name' => 'Cardiology',
            'specialty_code' => 'CARD',
        ]);
    }

    public function test_setting_primary_specialty_clears_existing(): void
    {
        $staff = Staff::factory()->create();

        $spec1 = $this->service->addSpecialty($staff, 'Cardiology', 'CARD', null, null, null, null, true);
        $spec2 = $this->service->addSpecialty($staff, 'Internal Medicine', 'IM', null, null, null, null, true);

        $this->assertEquals(1, $staff->specialties()->where('is_primary', true)->count());
    }

    public function test_can_remove_specialty(): void
    {
        $staff = Staff::factory()->create();
        $specialty = $staff->specialties()->create([
            'specialty_name' => 'Cardiology',
        ]);

        $result = $this->service->removeSpecialty($specialty);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('staff_specialties', ['id' => $specialty->id]);
    }

    public function test_can_get_staff_departments(): void
    {
        $staff = Staff::factory()->create();
        $dept1 = Department::factory()->create(['name' => 'Cardiology']);
        $dept2 = Department::factory()->create(['name' => 'Emergency']);

        $staff->assignDepartment($dept1, true);
        $staff->assignDepartment($dept2, false);

        $departments = $this->service->getStaffDepartments($staff);

        $this->assertEquals(2, $departments->count());
    }

    public function test_can_bulk_assign_departments(): void
    {
        $staff = Staff::factory()->create();
        $departments = Department::factory()->count(3)->create();

        $assignments = $this->service->bulkAssignDepartments(
            $staff,
            $departments->pluck('id')->toArray()
        );

        $this->assertEquals(3, $assignments->count());
    }

    public function test_can_transfer_between_departments(): void
    {
        $staff = Staff::factory()->create();
        $fromDept = Department::factory()->create();
        $toDept = Department::factory()->create();

        $staff->assignDepartment($fromDept, true);

        $assignment = $this->service->transferToDepartment($staff, $fromDept, $toDept);

        $this->assertEquals($toDept->id, $assignment->department_id);
        $this->assertTrue($assignment->is_primary);
        $this->assertDatabaseMissing('staff_departments', [
            'staff_id' => $staff->id,
            'department_id' => $fromDept->id,
            'end_date' => null,
        ]);
    }

    public function test_can_get_staff_assignments_summary(): void
    {
        $staff = Staff::factory()->create();
        $dept1 = Department::factory()->create();
        $dept2 = Department::factory()->create();

        $staff->assignDepartment($dept1, true);
        $staff->assignDepartment($dept2, false);

        $staff->specialties()->create(['specialty_name' => 'Cardiology', 'is_primary' => true]);
        $staff->specialties()->create(['specialty_name' => 'Internal Medicine']);

        $summary = $this->service->getStaffAssignmentsSummary($staff);

        $this->assertEquals(2, $summary['departments']['total']);
        $this->assertEquals(2, $summary['specialties']['total']);
    }
}
