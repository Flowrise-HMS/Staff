<?php

namespace Modules\Staff\Tests\Feature;

use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Modules\Core\Classes\Services\WardStaffAssignmentService;
use Modules\Core\Enums\WardStaffRole;
use Modules\Core\Filament\Clusters\Core\Resources\Locations\Pages\ListLocations;
use Modules\Core\Filament\Clusters\Core\Resources\Locations\Pages\ViewLocation;
use Modules\Core\Models\Branch;
use Modules\Core\Models\Location;
use Modules\Staff\Filament\Clusters\StaffCluster\Resources\Staff\Pages\ListStaff;
use Modules\Staff\Filament\Clusters\StaffCluster\Resources\Staff\Pages\ViewStaff;
use Modules\Staff\Models\Staff;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class WardStaffAssignmentTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;

    protected Branch $branch;

    protected Location $ward;

    protected Staff $staff;

    protected function setUp(): void
    {
        parent::setUp();
        $this->requireModule('Staff');
        $this->migrateModules(['Core', 'Staff']);

        $this->branch = Branch::factory()->create();
        $this->setCurrentBranch($this->branch);
        $this->admin = User::factory()->create(['branch_id' => $this->branch->id]);

        foreach ([
            'ViewAny Location', 'View Location', 'Update Location', 'View CoreCluster',
            'ViewAny Staff', 'View Staff', 'View StaffCluster',
        ] as $permission) {
            Permission::findOrCreate($permission, 'web');
            $this->admin->givePermissionTo($permission);
        }

        $this->ward = Location::factory()->room()->create(['branch_id' => $this->branch->id]);
        $this->staff = Staff::factory()->active()->create([
            'branch_id' => $this->branch->id,
            'user_id' => User::factory()->create(['branch_id' => $this->branch->id])->id,
        ]);
    }

    public function test_only_users_with_a_staff_profile_are_assignable(): void
    {
        $plainUser = User::factory()->create(['branch_id' => $this->branch->id]);
        $terminated = Staff::factory()->terminated()->create([
            'branch_id' => $this->branch->id,
            'user_id' => User::factory()->create(['branch_id' => $this->branch->id])->id,
        ]);

        $options = app(WardStaffAssignmentService::class)->assignableUserOptions();

        $this->assertArrayHasKey($this->staff->user_id, $options);
        $this->assertArrayNotHasKey($plainUser->id, $options);
        $this->assertArrayNotHasKey($terminated->user_id, $options);

        $this->expectException(ValidationException::class);
        app(WardStaffAssignmentService::class)->assign($this->ward, $plainUser->id, WardStaffRole::WARD_NURSE->value);
    }

    public function test_reassigning_a_staff_member_updates_the_existing_row(): void
    {
        $service = app(WardStaffAssignmentService::class);

        $service->assign($this->ward, $this->staff->user_id, WardStaffRole::WARD_NURSE->value);
        $service->assign($this->ward, $this->staff->user_id, WardStaffRole::DEPUTY_INCHARGE->value);

        $this->assertSame(1, $this->ward->wardStaff()->count());
        $this->assertTrue($this->ward->isUserIncharge($this->staff->user_id));
    }

    public function test_staff_can_be_assigned_from_the_locations_table(): void
    {
        Livewire::actingAs($this->admin)
            ->test(ListLocations::class)
            ->callAction(TestAction::make('assignWardStaff')->table($this->ward), [
                'user_id' => $this->staff->user_id,
                'assignment' => WardStaffRole::WARD_NURSE->value,
            ])
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('location_staff', [
            'location_id' => $this->ward->id,
            'user_id' => $this->staff->user_id,
            'role' => WardStaffRole::WARD_NURSE->value,
            'assigned_by' => $this->admin->id,
        ]);
    }

    public function test_assign_action_is_hidden_for_locations_that_are_not_wards(): void
    {
        $bed = Location::factory()->bed()->create(['branch_id' => $this->branch->id]);

        Livewire::actingAs($this->admin)
            ->test(ListLocations::class)
            ->assertActionHidden(TestAction::make('assignWardStaff')->table($bed))
            ->assertActionVisible(TestAction::make('assignWardStaff')->table($this->ward));
    }

    public function test_nurse_in_charge_can_be_assigned_from_the_location_view_page(): void
    {
        Livewire::actingAs($this->admin)
            ->test(ViewLocation::class, ['record' => $this->ward->getRouteKey()])
            ->callAction('assignWardStaff', [
                'user_id' => $this->staff->user_id,
                'assignment' => WardStaffAssignmentService::NURSE_IN_CHARGE,
            ])
            ->assertHasNoFormErrors();

        $this->assertSame($this->staff->user_id, $this->ward->fresh()->nurse_in_charge_id);
    }

    public function test_non_staff_users_are_rejected_by_the_location_action(): void
    {
        $plainUser = User::factory()->create(['branch_id' => $this->branch->id]);

        Livewire::actingAs($this->admin)
            ->test(ViewLocation::class, ['record' => $this->ward->getRouteKey()])
            ->callAction('assignWardStaff', [
                'user_id' => $plainUser->id,
                'assignment' => WardStaffRole::WARD_NURSE->value,
            ])
            ->assertHasFormErrors(['user_id']);

        $this->assertDatabaseMissing('location_staff', ['user_id' => $plainUser->id]);
    }

    public function test_staff_can_be_made_deputy_incharge_from_the_staff_table(): void
    {
        Livewire::actingAs($this->admin)
            ->test(ListStaff::class)
            ->callAction(TestAction::make('assignToWard')->table($this->staff), [
                'location_id' => $this->ward->id,
                'assignment' => WardStaffRole::DEPUTY_INCHARGE->value,
            ])
            ->assertHasNoFormErrors();

        $this->assertTrue($this->ward->isUserIncharge($this->staff->user_id));
    }

    public function test_staff_can_be_assigned_from_the_staff_view_page(): void
    {
        Livewire::actingAs($this->admin)
            ->test(ViewStaff::class, ['record' => $this->staff->getRouteKey()])
            ->callAction('assignToWard', [
                'location_id' => $this->ward->id,
                'assignment' => WardStaffAssignmentService::NURSE_IN_CHARGE,
            ])
            ->assertHasNoFormErrors();

        $this->assertSame($this->staff->user_id, $this->ward->fresh()->nurse_in_charge_id);
    }

    public function test_assign_to_ward_is_hidden_for_staff_without_a_user_account(): void
    {
        $noAccount = Staff::factory()->withoutUser()->create(['branch_id' => $this->branch->id]);

        Livewire::actingAs($this->admin)
            ->test(ListStaff::class)
            ->assertActionHidden(TestAction::make('assignToWard')->table($noAccount))
            ->assertActionVisible(TestAction::make('assignToWard')->table($this->staff));
    }
}
