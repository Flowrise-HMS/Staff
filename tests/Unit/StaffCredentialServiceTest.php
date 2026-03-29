<?php

namespace Modules\Staff\Tests\Unit;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Staff\Classes\Services\StaffCredentialService;
use Modules\Staff\Enums\CredentialStatus;
use Modules\Staff\Enums\CredentialType;
use Modules\Staff\Models\Staff;
use Modules\Staff\Models\StaffCredential;
use Tests\TestCase;

class StaffCredentialServiceTest extends TestCase
{
    use RefreshDatabase;

    protected StaffCredentialService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new StaffCredentialService;
    }

    public function test_can_create_credential(): void
    {
        $staff = Staff::factory()->create();

        $credential = $this->service->create($staff, [
            'credential_type' => CredentialType::MEDICAL_LICENSE,
            'credential_number' => 'GMC-123456',
            'expiry_date' => now()->addYear(),
        ]);

        $this->assertNotNull($credential->id);
        $this->assertEquals($staff->id, $credential->staff_id);
        $this->assertEquals('GMC-123456', $credential->credential_number);
    }

    public function test_can_verify_credential(): void
    {
        $credential = StaffCredential::factory()->pending()->create();
        $verifierId = User::factory()->create()->id;

        $result = $this->service->verify($credential, $verifierId, 'Verified');

        $this->assertEquals(CredentialStatus::VERIFIED, $result->status);
    }

    public function test_can_reject_credential(): void
    {
        $credential = StaffCredential::factory()->pending()->create();
        $rejectorId = User::factory()->create()->id;

        $result = $this->service->reject($credential, $rejectorId, 'Invalid document');

        $this->assertEquals(CredentialStatus::REJECTED, $result->status);
    }

    public function test_can_renew_credential(): void
    {
        $credential = StaffCredential::factory()->create([
            'expiry_date' => now()->subDays(10),
        ]);

        $newExpiry = now()->addYear();
        $result = $this->service->renew($credential, $newExpiry);

        $this->assertEquals($newExpiry->format('Y-m-d'), $result->expiry_date->format('Y-m-d'));
        $this->assertEquals(CredentialStatus::VERIFIED, $result->status);
    }

    public function test_can_bulk_verify(): void
    {
        $credentials = StaffCredential::factory()->pending()->count(3)->create();
        $verifierId = User::factory()->create()->id;

        $count = $this->service->bulkVerify($credentials->pluck('id')->toArray(), $verifierId);

        $this->assertEquals(3, $count);
    }

    public function test_can_get_expiring_credentials(): void
    {
        StaffCredential::factory()->expiringSoon(30)->count(2)->create();
        StaffCredential::factory()->verified()->count(3)->create(['expiry_date' => now()->addYear()]);

        $result = $this->service->getExpiringCredentials(30);

        $this->assertEquals(2, $result->count());
    }

    public function test_can_get_pending_verification(): void
    {
        StaffCredential::factory()->pending()->count(3)->create();
        StaffCredential::factory()->verified()->count(2)->create();

        $result = $this->service->getPendingVerification();

        $this->assertEquals(3, $result->count());
    }

    public function test_can_process_expired_credentials(): void
    {
        StaffCredential::factory()->verified()->create(['expiry_date' => now()->subDays(10)]);
        StaffCredential::factory()->verified()->create(['expiry_date' => now()->subDays(5)]);
        StaffCredential::factory()->pending()->create(['expiry_date' => now()->subDays(10)]);

        $count = $this->service->processExpiredCredentials();

        $this->assertEquals(2, $count);
    }

    public function test_can_get_credential_statistics(): void
    {
        StaffCredential::factory()->verified()->count(5)->create();
        StaffCredential::factory()->pending()->count(3)->create();
        StaffCredential::factory()->expired()->count(2)->create();

        $stats = $this->service->getCredentialStatistics();

        $this->assertEquals(10, $stats['total']);
        $this->assertEquals(5, $stats['verified']);
        $this->assertEquals(3, $stats['pending_verification']);
    }

    public function test_can_delete_credential(): void
    {
        $credential = StaffCredential::factory()->create();

        $result = $this->service->delete($credential);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('staff_credentials', ['id' => $credential->id]);
    }
}
