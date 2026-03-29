<?php

namespace Modules\Staff\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Core\Models\Department;
use Modules\Staff\Enums\CredentialType;
use Modules\Staff\Enums\EmploymentStatus;
use Modules\Staff\Enums\StaffType;
use Modules\Staff\Models\Staff;
use Tests\TestCase;

class StaffModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_staff(): void
    {
        $staff = Staff::factory()->create();

        $this->assertNotNull($staff->id);
        $this->assertNotNull($staff->staff_number);
        $this->assertNotNull($staff->first_name);
        $this->assertNotNull($staff->last_name);
    }

    public function test_staff_number_is_auto_generated(): void
    {
        $staff = Staff::factory()->create(['staff_number' => null]);

        $this->assertNotNull($staff->staff_number);
        $this->assertStringStartsWith('STF-', $staff->staff_number);
    }

    public function test_full_name_accessor(): void
    {
        $staff = Staff::factory()->create([
            'title' => 'Dr.',
            'first_name' => 'John',
            'middle_name' => 'Michael',
            'last_name' => 'Doe',
        ]);

        $this->assertEquals('Dr. John Michael Doe', $staff->full_name);
    }

    public function test_initials_accessor(): void
    {
        $staff = Staff::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        $this->assertEquals('JD', $staff->initials);
    }

    public function test_tenure_years_accessor(): void
    {
        $staff = Staff::factory()->create([
            'hire_date' => now()->subYears(3)->subMonths(6),
        ]);

        $this->assertEquals(3.5, $staff->tenure_years);
    }

    public function test_is_active_accessor(): void
    {
        $activeStaff = Staff::factory()->active()->create();
        $inactiveStaff = Staff::factory()->inactive()->create();
        $terminatedStaff = Staff::factory()->terminated()->create();

        $this->assertTrue($activeStaff->is_active);
        $this->assertFalse($inactiveStaff->is_active);
        $this->assertFalse($terminatedStaff->is_active);
    }

    public function test_has_user_account_accessor(): void
    {
        $staffWithUser = Staff::factory()->withUser()->create();
        $staffWithoutUser = Staff::factory()->withoutUser()->create();

        $this->assertTrue($staffWithUser->has_user_account);
        $this->assertFalse($staffWithoutUser->has_user_account);
    }

    public function test_termination_sets_termination_date(): void
    {
        $staff = Staff::factory()->active()->create();

        $staff->terminate('Voluntary resignation');

        $this->assertEquals(EmploymentStatus::TERMINATED, $staff->employment_status);
        $this->assertNotNull($staff->termination_date);
        $this->assertEquals('Voluntary resignation', $staff->termination_reason);
    }

    public function test_reactivation_clears_termination_data(): void
    {
        $staff = Staff::factory()->terminated()->create();

        $staff->reactivate();

        $this->assertEquals(EmploymentStatus::ACTIVE, $staff->employment_status);
        $this->assertNull($staff->termination_date);
        $this->assertNull($staff->termination_reason);
    }

    public function test_can_assign_department(): void
    {
        $staff = Staff::factory()->create();
        $department = Department::factory()->create();

        $assignment = $staff->assignDepartment($department, true);

        $this->assertDatabaseHas('staff_departments', [
            'staff_id' => $staff->id,
            'department_id' => $department->id,
            'is_primary' => true,
        ]);
    }

    public function test_can_add_specialty(): void
    {
        $staff = Staff::factory()->create();

        $specialty = $staff->specialties()->create([
            'specialty_name' => 'Cardiology',
            'specialty_code' => 'CARD',
            'is_primary' => true,
        ]);

        $this->assertDatabaseHas('staff_specialties', [
            'staff_id' => $staff->id,
            'specialty_name' => 'Cardiology',
            'is_primary' => true,
        ]);
    }

    public function test_can_add_credential(): void
    {
        $staff = Staff::factory()->create();

        $credential = $staff->credentials()->create([
            'credential_type' => CredentialType::MEDICAL_LICENSE,
            'credential_number' => 'GMC-123456',
        ]);

        $this->assertDatabaseHas('staff_credentials', [
            'staff_id' => $staff->id,
            'credential_number' => 'GMC-123456',
        ]);
    }

    public function test_active_scope(): void
    {
        Staff::factory()->active()->count(3)->create();
        Staff::factory()->inactive()->count(2)->create();

        $this->assertEquals(3, Staff::active()->count());
    }

    public function test_on_leave_scope(): void
    {
        Staff::factory()->active()->count(2)->create();
        Staff::factory()->onLeave()->count(3)->create();

        $this->assertEquals(3, Staff::onLeave()->count());
    }

    public function test_terminated_scope(): void
    {
        Staff::factory()->active()->count(2)->create();
        Staff::factory()->terminated()->count(4)->create();

        $this->assertEquals(4, Staff::terminated()->count());
    }

    public function test_by_type_scope(): void
    {
        Staff::factory()->fullTime()->count(3)->create();
        Staff::factory()->partTime()->count(2)->create();

        $this->assertEquals(3, Staff::byType(StaffType::FULL_TIME)->count());
    }

    public function test_staff_type_enum_casting(): void
    {
        $staff = Staff::factory()->create(['staff_type' => StaffType::CONTRACT]);

        $this->assertInstanceOf(StaffType::class, $staff->staff_type);
        $this->assertEquals(StaffType::CONTRACT, $staff->staff_type);
    }

    public function test_employment_status_enum_casting(): void
    {
        $staff = Staff::factory()->create(['employment_status' => EmploymentStatus::SUSPENDED]);

        $this->assertInstanceOf(EmploymentStatus::class, $staff->employment_status);
        $this->assertEquals(EmploymentStatus::SUSPENDED, $staff->employment_status);
    }

    public function test_soft_deletes(): void
    {
        $staff = Staff::factory()->create();
        $staff->delete();

        $this->assertSoftDeleted('staff', ['id' => $staff->id]);
        $this->assertNull(Staff::find($staff->id));
        $this->assertNotNull(Staff::withTrashed()->find($staff->id));
    }
}
