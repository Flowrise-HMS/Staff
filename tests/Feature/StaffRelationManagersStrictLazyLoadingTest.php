<?php

namespace Modules\Staff\Tests\Feature;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Modules\Core\Models\Branch;
use Modules\Core\Models\Department;
use Modules\Staff\Enums\CredentialType;
use Modules\Staff\Filament\Clusters\StaffCluster\Resources\Staff\Pages\ViewStaff;
use Modules\Staff\Filament\Clusters\StaffCluster\Resources\Staff\RelationManagers\CredentialsRelationManager;
use Modules\Staff\Filament\Clusters\StaffCluster\Resources\Staff\RelationManagers\DepartmentsRelationManager;
use Modules\Staff\Filament\Clusters\StaffCluster\Resources\Staff\RelationManagers\SpecialtiesRelationManager;
use Modules\Staff\Models\Staff;
use Modules\Staff\Models\StaffCredential;
use Modules\Staff\Models\StaffDepartment;
use Modules\Staff\Models\StaffSpecialty;
use Tests\TestCase;

/**
 * Relation managers load lazily on Livewire requests; with strict lazy
 * loading on (local/staging) any relation column without an eager load turns
 * that request into a 500 and the tab stays on "Loading".
 */
class StaffRelationManagersStrictLazyLoadingTest extends TestCase
{
    use DatabaseTransactions;

    private Staff $staff;

    protected function setUp(): void
    {
        parent::setUp();
        $this->migrateModules(['Core', 'Staff']);
        Model::preventLazyLoading(true);
        Gate::before(fn () => true);

        $branch = Branch::factory()->create();
        $this->staff = Staff::factory()->create(['branch_id' => $branch->id]);
        Livewire::actingAs(User::factory()->create(['branch_id' => $branch->id]));
    }

    protected function tearDown(): void
    {
        Model::preventLazyLoading(false);
        parent::tearDown();
    }

    public function test_credentials_relation_manager_renders(): void
    {
        $verifier = User::factory()->create();
        $types = array_slice(CredentialType::cases(), 0, 2);
        foreach ($types as $type) {
            StaffCredential::factory()->create([
                'staff_id' => $this->staff->id,
                'credential_type' => $type,
                'verified_by' => $verifier->id,
            ]);
        }

        Livewire::test(CredentialsRelationManager::class, [
            'ownerRecord' => $this->staff,
            'pageClass' => ViewStaff::class,
        ])->assertOk();
    }

    public function test_departments_relation_manager_renders(): void
    {
        StaffDepartment::factory()->count(2)->create([
            'staff_id' => $this->staff->id,
            'department_id' => fn () => Department::factory()->create()->id,
        ]);

        Livewire::test(DepartmentsRelationManager::class, [
            'ownerRecord' => $this->staff,
            'pageClass' => ViewStaff::class,
        ])->assertOk();
    }

    public function test_specialties_relation_manager_renders(): void
    {
        StaffSpecialty::factory()->count(2)->create(['staff_id' => $this->staff->id]);

        Livewire::test(SpecialtiesRelationManager::class, [
            'ownerRecord' => $this->staff,
            'pageClass' => ViewStaff::class,
        ])->assertOk();
    }
}
