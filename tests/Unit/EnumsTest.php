<?php

namespace Modules\Staff\Tests\Unit;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;
use Modules\Staff\Enums\CredentialStatus;
use Modules\Staff\Enums\CredentialType;
use Modules\Staff\Enums\EmploymentStatus;
use Modules\Staff\Enums\StaffType;
use PHPUnit\Framework\TestCase;

class EnumsTest extends TestCase
{
    public function test_staff_type_has_correct_values(): void
    {
        $this->assertEquals('full_time', StaffType::FULL_TIME->value);
        $this->assertEquals('part_time', StaffType::PART_TIME->value);
        $this->assertEquals('contract', StaffType::CONTRACT->value);
        $this->assertEquals('volunteer', StaffType::VOLUNTEER->value);
        $this->assertEquals('intern', StaffType::INTERN->value);
        $this->assertEquals('resident', StaffType::RESIDENT->value);
        $this->assertEquals('consultant', StaffType::CONSULTANT->value);
    }

    public function test_staff_type_labels(): void
    {
        $this->assertEquals('Full Time', StaffType::FULL_TIME->getLabel());
        $this->assertEquals('Part Time', StaffType::PART_TIME->getLabel());
        $this->assertEquals('Contract', StaffType::CONTRACT->getLabel());
    }

    public function test_staff_type_is_active(): void
    {
        $this->assertTrue(StaffType::FULL_TIME->isActive());
        $this->assertTrue(StaffType::PART_TIME->isActive());
        $this->assertFalse(StaffType::CONTRACT->isActive());
        $this->assertFalse(StaffType::VOLUNTEER->isActive());
    }

    public function test_staff_type_has_benefits(): void
    {
        $this->assertTrue(StaffType::FULL_TIME->hasBenefits());
        $this->assertTrue(StaffType::PART_TIME->hasBenefits());
        $this->assertFalse(StaffType::CONTRACT->hasBenefits());
        $this->assertFalse(StaffType::VOLUNTEER->hasBenefits());
    }

    public function test_staff_type_is_temporary(): void
    {
        $this->assertTrue(StaffType::CONTRACT->isTemporary());
        $this->assertTrue(StaffType::INTERN->isTemporary());
        $this->assertTrue(StaffType::VOLUNTEER->isTemporary());
        $this->assertFalse(StaffType::FULL_TIME->isTemporary());
    }

    public function test_employment_status_has_correct_values(): void
    {
        $this->assertEquals('active', EmploymentStatus::ACTIVE->value);
        $this->assertEquals('inactive', EmploymentStatus::INACTIVE->value);
        $this->assertEquals('on_leave', EmploymentStatus::ON_LEAVE->value);
        $this->assertEquals('suspended', EmploymentStatus::SUSPENDED->value);
        $this->assertEquals('terminated', EmploymentStatus::TERMINATED->value);
        $this->assertEquals('pending_verification', EmploymentStatus::PENDING_VERIFICATION->value);
    }

    public function test_employment_status_is_working(): void
    {
        $this->assertTrue(EmploymentStatus::ACTIVE->isWorking());
        $this->assertFalse(EmploymentStatus::INACTIVE->isWorking());
        $this->assertFalse(EmploymentStatus::ON_LEAVE->isWorking());
        $this->assertFalse(EmploymentStatus::TERMINATED->isWorking());
    }

    public function test_employment_status_can_perform_duties(): void
    {
        $this->assertTrue(EmploymentStatus::ACTIVE->canPerformDuties());
        $this->assertFalse(EmploymentStatus::SUSPENDED->canPerformDuties());
        $this->assertFalse(EmploymentStatus::TERMINATED->canPerformDuties());
    }

    public function test_employment_status_is_final(): void
    {
        $this->assertTrue(EmploymentStatus::TERMINATED->isFinal());
        $this->assertFalse(EmploymentStatus::ACTIVE->isFinal());
    }

    public function test_employment_status_requires_review(): void
    {
        $this->assertTrue(EmploymentStatus::SUSPENDED->requiresReview());
        $this->assertTrue(EmploymentStatus::PENDING_VERIFICATION->requiresReview());
        $this->assertFalse(EmploymentStatus::ACTIVE->requiresReview());
    }

    public function test_credential_status_has_correct_values(): void
    {
        $this->assertEquals('pending', CredentialStatus::PENDING->value);
        $this->assertEquals('verified', CredentialStatus::VERIFIED->value);
        $this->assertEquals('expired', CredentialStatus::EXPIRED->value);
        $this->assertEquals('rejected', CredentialStatus::REJECTED->value);
        $this->assertEquals('revoked', CredentialStatus::REVOKED->value);
        $this->assertEquals('under_review', CredentialStatus::UNDER_REVIEW->value);
    }

    public function test_credential_status_is_valid(): void
    {
        $this->assertTrue(CredentialStatus::VERIFIED->isValid());
        $this->assertFalse(CredentialStatus::PENDING->isValid());
        $this->assertFalse(CredentialStatus::EXPIRED->isValid());
    }

    public function test_credential_status_requires_action(): void
    {
        $this->assertTrue(CredentialStatus::PENDING->requiresAction());
        $this->assertTrue(CredentialStatus::EXPIRED->requiresAction());
        $this->assertFalse(CredentialStatus::VERIFIED->requiresAction());
    }

    public function test_credential_status_is_terminal(): void
    {
        $this->assertTrue(CredentialStatus::REJECTED->isTerminal());
        $this->assertTrue(CredentialStatus::REVOKED->isTerminal());
        $this->assertTrue(CredentialStatus::EXPIRED->isTerminal());
        $this->assertFalse(CredentialStatus::VERIFIED->isTerminal());
    }

    public function test_credential_type_requires_expiry(): void
    {
        $this->assertTrue(CredentialType::MEDICAL_LICENSE->requiresExpiry());
        $this->assertTrue(CredentialType::BLS_CERTIFICATION->requiresExpiry());
        $this->assertFalse(CredentialType::NPI_NUMBER->requiresExpiry());
        $this->assertFalse(CredentialType::DEA_REGISTRATION->requiresExpiry());
    }

    public function test_credential_type_is_life_support(): void
    {
        $this->assertTrue(CredentialType::BLS_CERTIFICATION->isLifeSupport());
        $this->assertTrue(CredentialType::ACLS_CERTIFICATION->isLifeSupport());
        $this->assertTrue(CredentialType::PALS_CERTIFICATION->isLifeSupport());
        $this->assertFalse(CredentialType::MEDICAL_LICENSE->isLifeSupport());
    }

    public function test_credential_type_is_license(): void
    {
        $this->assertTrue(CredentialType::MEDICAL_LICENSE->isLicense());
        $this->assertTrue(CredentialType::NURSING_LICENSE->isLicense());
        $this->assertTrue(CredentialType::PHARMACY_LICENSE->isLicense());
        $this->assertFalse(CredentialType::BLS_CERTIFICATION->isLicense());
    }

    public function test_all_enums_implement_interface(): void
    {
        $this->assertInstanceOf(HasLabel::class, StaffType::FULL_TIME);
        $this->assertInstanceOf(HasColor::class, StaffType::FULL_TIME);
        $this->assertInstanceOf(HasDescription::class, StaffType::FULL_TIME);
        $this->assertInstanceOf(HasLabel::class, EmploymentStatus::ACTIVE);
        $this->assertInstanceOf(HasColor::class, EmploymentStatus::ACTIVE);
        $this->assertInstanceOf(HasDescription::class, EmploymentStatus::ACTIVE);
        $this->assertInstanceOf(HasLabel::class, CredentialStatus::VERIFIED);
        $this->assertInstanceOf(HasColor::class, CredentialStatus::VERIFIED);
        $this->assertInstanceOf(HasDescription::class, CredentialStatus::VERIFIED);
        $this->assertInstanceOf(HasLabel::class, CredentialType::MEDICAL_LICENSE);
        $this->assertInstanceOf(HasColor::class, CredentialType::MEDICAL_LICENSE);
        $this->assertInstanceOf(HasDescription::class, CredentialType::MEDICAL_LICENSE);
    }

    public function test_enums_have_values_method(): void
    {
        $this->assertContains('full_time', StaffType::values());
        $this->assertContains('active', EmploymentStatus::values());
        $this->assertContains('verified', CredentialStatus::values());
    }

    public function test_enums_have_default_method(): void
    {
        $this->assertEquals(StaffType::FULL_TIME, StaffType::default());
        $this->assertEquals(EmploymentStatus::PENDING_VERIFICATION, EmploymentStatus::default());
        $this->assertEquals(CredentialStatus::PENDING, CredentialStatus::default());
    }
}
