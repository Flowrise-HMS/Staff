<?php

namespace Modules\Staff\Tests\Unit;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Staff\Classes\Services\StaffService;
use Modules\Staff\Enums\EmploymentStatus;
use Modules\Staff\Enums\StaffType;
use Modules\Staff\Models\Staff;
use Tests\TestCase;

class StaffServiceTest extends TestCase
{
    use DatabaseTransactions;

    protected StaffService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->migrateModules(['Core', 'Staff']);
        $this->service = new StaffService;
    }

    public function test_can_get_all_staff(): void
    {
        Staff::factory()->count(5)->create();

        $result = $this->service->getAll();

        $this->assertEquals(5, $result->total());
    }

    public function test_can_filter_by_status(): void
    {
        Staff::factory()->active()->count(3)->create();
        Staff::factory()->inactive()->count(2)->create();

        $result = $this->service->getAll(['status' => EmploymentStatus::ACTIVE]);

        $this->assertEquals(3, $result->total());
    }

    public function test_can_filter_by_type(): void
    {
        Staff::factory()->fullTime()->count(4)->create();
        Staff::factory()->partTime()->count(2)->create();

        $result = $this->service->getAll(['type' => StaffType::FULL_TIME]);

        $this->assertEquals(4, $result->total());
    }

    public function test_can_search_staff(): void
    {
        Staff::factory()->create(['first_name' => 'John', 'last_name' => 'Doe']);
        Staff::factory()->create(['first_name' => 'Jane', 'last_name' => 'Smith']);

        $result = $this->service->getAll(['search' => 'John']);

        $this->assertEquals(1, $result->total());
    }

    public function test_can_get_active_staff(): void
    {
        Staff::factory()->active()->count(3)->create();
        Staff::factory()->terminated()->count(2)->create();

        $result = $this->service->getActive();

        $this->assertEquals(3, $result->count());
    }

    public function test_can_find_by_id(): void
    {
        $staff = Staff::factory()->create();

        $result = $this->service->findById($staff->id);

        $this->assertEquals($staff->id, $result->id);
    }

    public function test_can_find_by_staff_number(): void
    {
        $staff = Staff::factory()->create();

        $result = $this->service->findByStaffNumber($staff->staff_number);

        $this->assertEquals($staff->id, $result->id);
    }

    public function test_can_create_staff(): void
    {
        $data = [
            'first_name' => 'Kwame',
            'last_name' => 'Asante',
            'staff_type' => StaffType::FULL_TIME,
            'employment_status' => EmploymentStatus::ACTIVE,
        ];

        $staff = $this->service->create($data);

        $this->assertNotNull($staff->id);
        $this->assertEquals('Kwame', $staff->first_name);
        $this->assertEquals('Asante', $staff->last_name);
        $this->assertNotNull($staff->staff_number);
    }

    public function test_can_update_staff(): void
    {
        $staff = Staff::factory()->create();

        $result = $this->service->update($staff, [
            'first_name' => 'Updated',
        ]);

        $this->assertEquals('Updated', $result->first_name);
    }

    public function test_can_terminate_staff(): void
    {
        $staff = Staff::factory()->active()->create();

        $result = $this->service->terminate($staff, 'Voluntary resignation');

        $this->assertEquals(EmploymentStatus::TERMINATED, $result->employment_status);
        $this->assertNotNull($result->termination_date);
    }

    public function test_can_reactivate_staff(): void
    {
        $staff = Staff::factory()->terminated()->create();

        $result = $this->service->reactivate($staff);

        $this->assertEquals(EmploymentStatus::ACTIVE, $result->employment_status);
    }

    public function test_can_get_statistics(): void
    {
        Staff::factory()->active()->count(10)->create();
        Staff::factory()->terminated()->count(2)->create();
        Staff::factory()->withVerifiedCredentials(2)->create();

        $stats = $this->service->getStatistics();

        $this->assertEquals(13, $stats['total']);
        $this->assertEquals(11, $stats['active']);
        $this->assertEquals(2, $stats['terminated']);
    }

    public function test_can_link_user_account(): void
    {
        $staff = Staff::factory()->withoutUser()->create();
        $user = User::factory()->create();

        $result = $this->service->linkUserAccount($staff, $user->id);

        $this->assertEquals($user->id, $result->user_id);
    }

    public function test_cannot_link_user_to_existing_staff(): void
    {
        $staff1 = Staff::factory()->withUser()->create();
        $staff2 = Staff::factory()->withoutUser()->create();

        $this->expectException(\Exception::class);

        $this->service->linkUserAccount($staff2, $staff1->user_id);
    }

    public function test_can_unlink_user_account(): void
    {
        $staff = Staff::factory()->withUser()->create();

        $result = $this->service->unlinkUserAccount($staff);

        $this->assertNull($result->user_id);
    }
}
