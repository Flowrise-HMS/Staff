<?php

namespace Modules\Staff\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Context;
use Modules\Core\Models\Branch;
use Modules\Core\Models\Organization;
use Modules\Staff\Http\Resources\StaffTransformer;
use Modules\Staff\Models\Staff;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Reference API test set. Every resource added after this one is expected to carry
 * the same six cases.
 *
 * Note that no test here authenticates as a super_admin: CoreServiceProvider
 * registers a `Gate::before` that grants super_admin every ability, so a test using
 * one would pass regardless of whether the policy or the permission check works.
 */
class StaffApiTest extends TestCase
{
    use DatabaseTransactions;

    private Branch $branchA;

    private Branch $branchB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->migrateModules(['Core', 'Staff']);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (['ViewAny Staff', 'View Staff', 'Create Staff', 'Update Staff'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $organization = Organization::factory()->create([
            'name' => 'Test Org',
            'display_name' => 'Test Org',
            'is_active' => true,
        ]);

        $this->branchA = Branch::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'Branch A',
            'display_name' => 'Branch A',
            'is_active' => true,
        ]);

        $this->branchB = Branch::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'Branch B',
            'display_name' => 'Branch B',
            'is_active' => true,
        ]);
    }

    private function userWith(array $permissions, ?Branch $branch = null): User
    {
        $user = User::factory()->create(['branch_id' => ($branch ?? $this->branchA)->id]);
        $user->givePermissionTo($permissions);

        return $user;
    }

    private function staffIn(Branch $branch): Staff
    {
        Context::add('current_branch_id', $branch->id);
        $staff = Staff::factory()->create(['branch_id' => $branch->id]);
        Context::forget('current_branch_id');

        return $staff;
    }

    public function test_unauthenticated_request_returns_401(): void
    {
        $this->getJson('/api/v1/staff')->assertStatus(401);
    }

    public function test_authenticated_user_without_permission_returns_403(): void
    {
        $user = $this->userWith([]);

        $this->actingAs($user)->getJson('/api/v1/staff')->assertStatus(403);
    }

    public function test_user_with_permission_can_list_staff(): void
    {
        $this->staffIn($this->branchA);
        $user = $this->userWith(['ViewAny Staff']);

        $response = $this->actingAs($user)->getJson('/api/v1/staff');

        $response->assertSuccessful();
        $response->assertJsonStructure([
            'success',
            'data' => ['*' => ['id', 'staff_number', 'first_name', 'last_name']],
            'meta' => ['current_page', 'per_page', 'total', 'last_page'],
        ]);
    }

    public function test_staff_from_another_branch_returns_404(): void
    {
        $otherBranchStaff = $this->staffIn($this->branchB);
        $user = $this->userWith(['View Staff'], $this->branchA);

        $response = $this->actingAs($user)->getJson("/api/v1/staff/{$otherBranchStaff->id}");

        $response->assertStatus(404);
        $response->assertDontSee($otherBranchStaff->last_name);
    }

    public function test_listing_excludes_other_branches(): void
    {
        $mine = $this->staffIn($this->branchA);
        $theirs = $this->staffIn($this->branchB);
        $user = $this->userWith(['ViewAny Staff']);

        $response = $this->actingAs($user)->getJson('/api/v1/staff');

        $ids = array_column($response->json('data'), 'id');
        $this->assertContains($mine->id, $ids);
        $this->assertNotContains($theirs->id, $ids);
    }

    public function test_response_contains_only_allow_listed_fields(): void
    {
        $staff = $this->staffIn($this->branchA);
        $user = $this->userWith(['View Staff']);

        $response = $this->actingAs($user)->getJson("/api/v1/staff/{$staff->id}");

        $response->assertSuccessful();

        $allowed = (new StaffTransformer($staff))->toArray(request());

        $this->assertSame(
            [],
            array_diff(array_keys($response->json('data')), array_keys($allowed)),
            'Response exposed fields outside the transformer allow-list.',
        );
    }

    public function test_personal_data_is_not_exposed(): void
    {
        $staff = $this->staffIn($this->branchA);
        $user = $this->userWith(['View Staff']);

        $data = $this->actingAs($user)->getJson("/api/v1/staff/{$staff->id}")->json('data');

        foreach (['date_of_birth', 'address', 'emergency_contact', 'notes', 'metadata', 'branch_id'] as $withheld) {
            $this->assertArrayNotHasKey($withheld, $data);
        }
    }

    public function test_staff_can_be_created(): void
    {
        $user = $this->userWith(['Create Staff']);

        $response = $this->actingAs($user)->postJson('/api/v1/staff', [
            'first_name' => 'Ama',
            'last_name' => 'Mensah',
            'staff_number' => 'STF-TEST-0001',
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.first_name', 'Ama');

        $this->assertDatabaseHas('staff', [
            'staff_number' => 'STF-TEST-0001',
            'branch_id' => $this->branchA->id,
        ]);
    }

    public function test_created_staff_is_pinned_to_the_callers_branch(): void
    {
        $user = $this->userWith(['Create Staff'], $this->branchA);

        $this->actingAs($user)->postJson('/api/v1/staff', [
            'first_name' => 'Kofi',
            'last_name' => 'Owusu',
            'staff_number' => 'STF-TEST-0002',
            'branch_id' => $this->branchB->id,
        ])->assertStatus(201);

        $this->assertDatabaseHas('staff', [
            'staff_number' => 'STF-TEST-0002',
            'branch_id' => $this->branchA->id,
        ]);
        $this->assertDatabaseMissing('staff', [
            'staff_number' => 'STF-TEST-0002',
            'branch_id' => $this->branchB->id,
        ]);
    }

    public function test_staff_can_be_updated(): void
    {
        $staff = $this->staffIn($this->branchA);
        $user = $this->userWith(['Update Staff']);

        $response = $this->actingAs($user)->putJson("/api/v1/staff/{$staff->id}", [
            'first_name' => 'Renamed',
            'last_name' => $staff->last_name,
        ]);

        $response->assertSuccessful();
        $response->assertJsonPath('data.first_name', 'Renamed');
    }

    public function test_writes_are_recorded_in_the_activity_log(): void
    {
        $user = $this->userWith(['Create Staff']);

        $this->actingAs($user)->postJson('/api/v1/staff', [
            'first_name' => 'Audited',
            'last_name' => 'Person',
            'staff_number' => 'STF-TEST-0003',
        ])->assertStatus(201);

        $staff = Staff::withoutGlobalScopes()->where('staff_number', 'STF-TEST-0003')->firstOrFail();

        $this->assertDatabaseHas('activity_log', [
            'subject_type' => Staff::class,
            'subject_id' => $staff->id,
        ]);
    }
}
