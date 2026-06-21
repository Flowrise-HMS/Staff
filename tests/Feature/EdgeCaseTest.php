<?php

namespace Modules\Staff\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Staff\Enums\CredentialStatus;
use Modules\Staff\Enums\CredentialType;
use Modules\Staff\Enums\EmploymentStatus;
use Modules\Staff\Enums\StaffType;
use Modules\Staff\Models\Staff;
use Modules\Staff\Models\StaffCredential;
use Modules\Staff\Models\StaffDepartment;
use Modules\Staff\Models\StaffSpecialty;
use Tests\TestCase;

class EdgeCaseTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->migrateModules(['Core', 'Staff']);
    }

    // ─── Staff model ────────────────────────────────────────────────────────

    public function test_staff_has_uuid(): void
    {
        $staff = Staff::factory()->create();
        $this->assertNotNull($staff->id);
    }

    public function test_staff_auto_generates_number(): void
    {
        $staff = Staff::factory()->create(['staff_number' => null]);
        $this->assertNotNull($staff->staff_number);
        $this->assertStringStartsWith('STF-', $staff->staff_number);
    }

    public function test_staff_casts_employment_status_as_enum(): void
    {
        $staff = Staff::factory()->create(['employment_status' => EmploymentStatus::ACTIVE]);
        $this->assertTrue($staff->employment_status instanceof EmploymentStatus);
        $this->assertSame(EmploymentStatus::ACTIVE, $staff->employment_status);
    }

    public function test_staff_get_full_name(): void
    {
        $staff = Staff::factory()->create([
            'first_name' => 'Jane',
            'last_name' => 'Smith',
        ]);
        $this->assertStringContainsString('Jane', $staff->full_name);
        $this->assertStringContainsString('Smith', $staff->full_name);
    }

    public function test_staff_get_initials(): void
    {
        $staff = Staff::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);
        $this->assertSame('JD', $staff->initials);
    }

    public function test_staff_initials_fallback(): void
    {
        $staff = Staff::make([
            'first_name' => null,
            'last_name' => null,
        ]);
        $this->assertSame('??', $staff->initials);
    }

    public function test_staff_is_active_check(): void
    {
        $active = Staff::factory()->create(['employment_status' => EmploymentStatus::ACTIVE]);
        $this->assertTrue($active->is_active);
        $terminated = Staff::factory()->create(['employment_status' => EmploymentStatus::TERMINATED]);
        $this->assertFalse($terminated->is_active);
    }

    public function test_staff_terminate_sets_date_and_reason(): void
    {
        $staff = Staff::factory()->create(['employment_status' => EmploymentStatus::ACTIVE]);
        $staff->terminate('Resigned');
        $this->assertSame(EmploymentStatus::TERMINATED, $staff->employment_status);
        $this->assertNotNull($staff->termination_date);
        $this->assertSame('Resigned', $staff->termination_reason);
    }

    public function test_staff_terminate_sets_termination_date_automatically(): void
    {
        $staff = Staff::factory()->create(['employment_status' => EmploymentStatus::ACTIVE]);
        $staff->terminate();
        $this->assertNotNull($staff->termination_date);
    }

    public function test_staff_reactivate(): void
    {
        $staff = Staff::factory()->create(['employment_status' => EmploymentStatus::TERMINATED]);
        $staff->reactivate();
        $this->assertSame(EmploymentStatus::ACTIVE, $staff->employment_status);
        $this->assertNull($staff->termination_date);
        $this->assertNull($staff->termination_reason);
    }

    // ─── StaffCredential model ──────────────────────────────────────────────

    public function test_credential_has_uuid(): void
    {
        $credential = StaffCredential::factory()->create();
        $this->assertNotNull($credential->id);
    }

    public function test_credential_belongs_to_staff(): void
    {
        $credential = StaffCredential::factory()->create();
        $this->assertNotNull($credential->staff);
    }

    // ─── StaffDepartment model ──────────────────────────────────────────────

    public function test_staff_department_has_uuid(): void
    {
        $assignment = StaffDepartment::factory()->create();
        $this->assertNotNull($assignment->id);
    }

    // ─── StaffSpecialty model ───────────────────────────────────────────────

    public function test_specialty_has_uuid(): void
    {
        $specialty = StaffSpecialty::factory()->create();
        $this->assertNotNull($specialty->id);
    }

    // ─── CredentialStatus enum ──────────────────────────────────────────────

    public function test_credential_status_values(): void
    {
        $values = CredentialStatus::values();
        $this->assertContains('pending', $values);
        $this->assertContains('verified', $values);
        $this->assertContains('expired', $values);
        $this->assertContains('rejected', $values);
        $this->assertContains('revoked', $values);
        $this->assertContains('under_review', $values);
        $this->assertCount(6, $values);
    }

    public function test_credential_status_default(): void
    {
        $this->assertSame(CredentialStatus::PENDING, CredentialStatus::default());
    }

    public function test_credential_status_is_valid(): void
    {
        $this->assertTrue(CredentialStatus::VERIFIED->isValid());
        $this->assertFalse(CredentialStatus::PENDING->isValid());
        $this->assertFalse(CredentialStatus::EXPIRED->isValid());
        $this->assertFalse(CredentialStatus::REJECTED->isValid());
    }

    public function test_credential_status_is_terminal(): void
    {
        $this->assertTrue(CredentialStatus::REJECTED->isTerminal());
        $this->assertTrue(CredentialStatus::REVOKED->isTerminal());
        $this->assertTrue(CredentialStatus::EXPIRED->isTerminal());
        $this->assertFalse(CredentialStatus::PENDING->isTerminal());
        $this->assertFalse(CredentialStatus::VERIFIED->isTerminal());
    }

    // ─── EmploymentStatus enum ──────────────────────────────────────────────

    public function test_employment_status_values(): void
    {
        $values = EmploymentStatus::values();
        $this->assertContains('active', $values);
        $this->assertContains('inactive', $values);
        $this->assertContains('on_leave', $values);
        $this->assertContains('suspended', $values);
        $this->assertContains('terminated', $values);
        $this->assertContains('pending_verification', $values);
        $this->assertCount(6, $values);
    }

    public function test_employment_status_default(): void
    {
        $this->assertSame(EmploymentStatus::PENDING_VERIFICATION, EmploymentStatus::default());
    }

    public function test_employment_status_is_working(): void
    {
        $this->assertTrue(EmploymentStatus::ACTIVE->isWorking());
        $this->assertFalse(EmploymentStatus::INACTIVE->isWorking());
        $this->assertFalse(EmploymentStatus::TERMINATED->isWorking());
        $this->assertFalse(EmploymentStatus::SUSPENDED->isWorking());
    }

    // ─── StaffType enum ─────────────────────────────────────────────────────

    public function test_staff_type_values(): void
    {
        $values = StaffType::values();
        $this->assertContains('full_time', $values);
        $this->assertContains('part_time', $values);
        $this->assertContains('contract', $values);
        $this->assertContains('volunteer', $values);
        $this->assertContains('intern', $values);
        $this->assertContains('resident', $values);
        $this->assertContains('consultant', $values);
    }

    // ─── CredentialType enum ────────────────────────────────────────────────

    public function test_credential_type_values(): void
    {
        $values = CredentialType::values();
        $this->assertContains('medical_license', $values);
        $this->assertContains('bls_certification', $values);
        $this->assertContains('board_certification', $values);
        $this->assertContains('npi_number', $values);
    }
}
