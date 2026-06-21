<?php

namespace Modules\Staff\Tests\Unit;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Staff\Enums\CredentialStatus;
use Modules\Staff\Enums\CredentialType;
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

    public function test_can_create_credential(): void
    {
        $staff = Staff::factory()->create();

        $credential = StaffCredential::factory()->create([
            'staff_id' => $staff->id,
            'credential_type' => CredentialType::MEDICAL_LICENSE,
        ]);

        $this->assertNotNull($credential->id);
        $this->assertEquals($staff->id, $credential->staff_id);
    }

    public function test_credential_type_enum_casting(): void
    {
        $credential = StaffCredential::factory()->create([
            'credential_type' => CredentialType::BLS_CERTIFICATION,
        ]);

        $this->assertInstanceOf(CredentialType::class, $credential->credential_type);
        $this->assertEquals(CredentialType::BLS_CERTIFICATION, $credential->credential_type);
    }

    public function test_status_enum_casting(): void
    {
        $credential = StaffCredential::factory()->create([
            'status' => CredentialStatus::PENDING,
        ]);

        $this->assertInstanceOf(CredentialStatus::class, $credential->status);
        $this->assertEquals(CredentialStatus::PENDING, $credential->status);
    }

    public function test_verify_sets_verified_status(): void
    {
        $credential = StaffCredential::factory()->pending()->create();
        $verifierId = User::factory()->create()->id;

        $credential->verify($verifierId, 'Document verified');

        $this->assertEquals(CredentialStatus::VERIFIED, $credential->status);
        $this->assertEquals($verifierId, $credential->verified_by);
        $this->assertNotNull($credential->verified_at);
    }

    public function test_reject_sets_rejected_status(): void
    {
        $credential = StaffCredential::factory()->pending()->create();
        $rejectorId = User::factory()->create()->id;

        $credential->reject($rejectorId, 'Invalid document');

        $this->assertEquals(CredentialStatus::REJECTED, $credential->status);
        $this->assertEquals($rejectorId, $credential->verified_by);
        $this->assertEquals('Invalid document', $credential->rejection_reason);
    }

    public function test_mark_as_expired(): void
    {
        $credential = StaffCredential::factory()->verified()->create([
            'expiry_date' => now()->subDays(10),
        ]);

        $credential->markAsExpired();

        $this->assertEquals(CredentialStatus::EXPIRED, $credential->status);
    }

    public function test_renew_updates_expiry_and_status(): void
    {
        $credential = StaffCredential::factory()->create([
            'expiry_date' => now()->subDays(10),
        ]);

        $newExpiry = now()->addYear();
        $credential->renew($newExpiry);

        $this->assertEquals($newExpiry->format('Y-m-d'), $credential->expiry_date->format('Y-m-d'));
        $this->assertEquals(CredentialStatus::VERIFIED, $credential->status);
    }

    public function test_is_expired_accessor(): void
    {
        $expiredCredential = StaffCredential::factory()->create([
            'expiry_date' => now()->subDays(10),
        ]);

        $validCredential = StaffCredential::factory()->create([
            'expiry_date' => now()->addDays(30),
        ]);

        $noExpiryCredential = StaffCredential::factory()->create([
            'expiry_date' => null,
        ]);

        $this->assertTrue($expiredCredential->is_expired);
        $this->assertFalse($validCredential->is_expired);
        $this->assertFalse($noExpiryCredential->is_expired);
    }

    public function test_is_expiring_soon_accessor(): void
    {
        $expiringCredential = StaffCredential::factory()->create([
            'expiry_date' => now()->addDays(15),
        ]);

        $notExpiringCredential = StaffCredential::factory()->create([
            'expiry_date' => now()->addDays(60),
        ]);

        $this->assertTrue($expiringCredential->is_expiring_soon);
        $this->assertFalse($notExpiringCredential->is_expiring_soon);
    }

    public function test_days_until_expiry_accessor(): void
    {
        $targetDays = 30;
        $credential = StaffCredential::factory()->create([
            'expiry_date' => now()->addDays($targetDays)->startOfDay(),
        ]);

        $this->assertEqualsWithDelta($targetDays, $credential->days_until_expiry, 1);
    }

    public function test_is_valid_accessor(): void
    {
        $validCredential = StaffCredential::factory()->verified()->create([
            'expiry_date' => now()->addDays(30),
        ]);

        $pendingCredential = StaffCredential::factory()->pending()->create([
            'expiry_date' => now()->addDays(30),
        ]);

        $expiredCredential = StaffCredential::factory()->verified()->create([
            'expiry_date' => now()->subDays(10),
        ]);

        $this->assertTrue($validCredential->is_valid);
        $this->assertFalse($pendingCredential->is_valid);
        $this->assertFalse($expiredCredential->is_valid);
    }

    public function test_pending_scope(): void
    {
        StaffCredential::factory()->pending()->count(3)->create();
        StaffCredential::factory()->verified()->count(2)->create();

        $this->assertEquals(3, StaffCredential::pending()->count());
    }

    public function test_verified_scope(): void
    {
        StaffCredential::factory()->pending()->count(2)->create();
        StaffCredential::factory()->verified()->count(4)->create();

        $this->assertEquals(4, StaffCredential::verified()->count());
    }

    public function test_expiring_soon_scope(): void
    {
        StaffCredential::factory()->expiringSoon(30)->count(3)->create();
        StaffCredential::factory()->verified()->count(2)->create(['expiry_date' => now()->addYear()]);

        $this->assertEquals(3, StaffCredential::expiringSoon(30)->count());
    }

    public function test_staff_relationship(): void
    {
        $staff = Staff::factory()->create();
        $credential = StaffCredential::factory()->create(['staff_id' => $staff->id]);

        $this->assertEquals($staff->id, $credential->staff->id);
    }
}
