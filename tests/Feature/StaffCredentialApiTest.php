<?php

namespace Modules\Staff\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Context;
use Modules\Core\Models\Branch;
use Modules\Core\Models\Organization;
use Modules\Staff\Enums\CredentialStatus;
use Modules\Staff\Models\Staff;
use Modules\Staff\Models\StaffCredential;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * StaffCredential is the reference case for a resource with neither its own policy
 * nor a branch column: authorization and branch isolation are both inherited from
 * the parent Staff record.
 *
 * The cross-branch test below deliberately seeds the credential against a staff
 * member in the *other* branch. A test that seeded the credential alone would pass
 * vacuously, because the credential itself carries nothing to scope on.
 */
class StaffCredentialApiTest extends TestCase
{
    use DatabaseTransactions;

    private Branch $branchA;

    private Branch $branchB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->migrateModules(['Core', 'Staff']);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (['View Staff', 'Update Staff'] as $permission) {
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
        $staff = $this->staffIn($this->branchA);

        $this->getJson("/api/v1/staff/{$staff->id}/credentials")->assertStatus(401);
    }

    public function test_user_without_permission_returns_403(): void
    {
        $staff = $this->staffIn($this->branchA);
        $user = $this->userWith([]);

        $this->actingAs($user)
            ->getJson("/api/v1/staff/{$staff->id}/credentials")
            ->assertStatus(403);
    }

    public function test_credentials_can_be_listed_for_a_staff_member(): void
    {
        $staff = $this->staffIn($this->branchA);
        StaffCredential::factory()->create(['staff_id' => $staff->id]);
        $user = $this->userWith(['View Staff']);

        $response = $this->actingAs($user)->getJson("/api/v1/staff/{$staff->id}/credentials");

        $response->assertSuccessful();
        $response->assertJsonStructure([
            'success',
            'data' => ['*' => ['id', 'staff_id', 'credential_type', 'credential_number', 'status']],
            'meta' => ['current_page', 'per_page', 'total', 'last_page'],
        ]);
    }

    public function test_credentials_of_a_staff_member_in_another_branch_return_404(): void
    {
        $otherBranchStaff = $this->staffIn($this->branchB);
        $credential = StaffCredential::factory()->create(['staff_id' => $otherBranchStaff->id]);
        $user = $this->userWith(['View Staff'], $this->branchA);

        $response = $this->actingAs($user)
            ->getJson("/api/v1/staff/{$otherBranchStaff->id}/credentials");

        $response->assertStatus(404);
        $response->assertDontSee($credential->credential_number);
    }

    public function test_a_credential_cannot_be_updated_through_another_staff_member(): void
    {
        $mine = $this->staffIn($this->branchA);
        $theirs = $this->staffIn($this->branchA);
        $credential = StaffCredential::factory()->create(['staff_id' => $theirs->id]);
        $user = $this->userWith(['Update Staff']);

        $this->actingAs($user)
            ->putJson("/api/v1/staff/{$mine->id}/credentials/{$credential->id}", [
                'credential_type' => $credential->credential_type->value,
                'credential_number' => $credential->credential_number,
                'issuing_authority' => 'Rewritten Authority',
            ])
            ->assertStatus(404);

        $this->assertDatabaseHas('staff_credentials', [
            'id' => $credential->id,
            'issuing_authority' => $credential->issuing_authority,
        ]);
    }

    public function test_credential_can_be_created_under_its_parent(): void
    {
        $staff = $this->staffIn($this->branchA);
        $user = $this->userWith(['Update Staff']);

        $response = $this->actingAs($user)
            ->postJson("/api/v1/staff/{$staff->id}/credentials", [
                'credential_type' => 'medical_license',
                'credential_number' => 'GH123456',
                'issuing_authority' => 'Ghana Medical and Dental Council',
            ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.staff_id', $staff->id);

        $this->assertDatabaseHas('staff_credentials', [
            'credential_number' => 'GH123456',
            'staff_id' => $staff->id,
        ]);
    }

    public function test_staff_id_in_the_body_is_ignored(): void
    {
        $staff = $this->staffIn($this->branchA);
        $otherStaff = $this->staffIn($this->branchA);
        $user = $this->userWith(['Update Staff']);

        $this->actingAs($user)
            ->postJson("/api/v1/staff/{$staff->id}/credentials", [
                'staff_id' => $otherStaff->id,
                'credential_type' => 'medical_license',
                'credential_number' => 'GH999999',
                'issuing_authority' => 'Ghana Medical and Dental Council',
            ])
            ->assertStatus(201);

        $this->assertDatabaseHas('staff_credentials', [
            'credential_number' => 'GH999999',
            'staff_id' => $staff->id,
        ]);
    }

    public function test_verification_fields_are_not_client_writable(): void
    {
        $staff = $this->staffIn($this->branchA);
        $credential = StaffCredential::factory()->create([
            'staff_id' => $staff->id,
            'status' => CredentialStatus::PENDING,
            'verified_by' => null,
            'verified_at' => null,
        ]);
        $user = $this->userWith(['Update Staff']);

        $this->actingAs($user)
            ->putJson("/api/v1/staff/{$staff->id}/credentials/{$credential->id}", [
                'credential_type' => $credential->credential_type->value,
                'credential_number' => $credential->credential_number,
                'issuing_authority' => $credential->issuing_authority,
                'verified_by' => $user->id,
                'verified_at' => now()->toIso8601String(),
                'document_path' => '/etc/passwd',
            ])
            ->assertSuccessful();

        $this->assertDatabaseHas('staff_credentials', [
            'id' => $credential->id,
            'verified_by' => null,
            'verified_at' => null,
            'document_path' => $credential->document_path,
        ]);
    }

    public function test_response_withholds_internal_review_fields(): void
    {
        $staff = $this->staffIn($this->branchA);
        StaffCredential::factory()->create(['staff_id' => $staff->id]);
        $user = $this->userWith(['View Staff']);

        $data = $this->actingAs($user)
            ->getJson("/api/v1/staff/{$staff->id}/credentials")
            ->json('data.0');

        foreach (['document_path', 'verification_notes', 'rejection_reason', 'verified_by', 'metadata'] as $withheld) {
            $this->assertArrayNotHasKey($withheld, $data);
        }
    }
}
