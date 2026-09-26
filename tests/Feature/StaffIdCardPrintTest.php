<?php

namespace Modules\Staff\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Context;
use Modules\Core\Database\Factories\BranchFactory;
use Modules\Core\Models\Department;
use Modules\Core\Settings\FeatureSettings;
use Modules\Core\Tests\Support\AssertsOfflinePrintHtml;
use Modules\Staff\Http\Controllers\StaffIdCardsBulkController;
use Modules\Staff\Models\Staff;
use Modules\Staff\Models\StaffDepartment;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class StaffIdCardPrintTest extends TestCase
{
    use AssertsOfflinePrintHtml;
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->migrateModules(['Core', 'Staff']);
    }

    public function test_staff_id_card_print_view_uses_only_local_assets(): void
    {
        Permission::firstOrCreate(['name' => 'print_staff_id', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'View Staff', 'guard_name' => 'web']);

        $branch = BranchFactory::new()->create();
        Context::add('current_branch_id', $branch->id);

        $staff = Staff::factory()->create([
            'branch_id' => $branch->id,
            'staff_number' => 'STF-TEST-001',
        ]);

        $user = User::factory()->create();
        $user->givePermissionTo(['print_staff_id', 'View Staff']);

        $response = $this->actingAs($user)->get(route('staff.id-card', $staff));

        $response->assertOk();
        $html = (string) $response->getContent();
        $this->assertPrintHtmlIsOffline($html);
        $this->assertStringContainsString('css/print/id-card.css', $html);
        $this->assertFileExists(public_path('fonts/LibreBarcode128-Regular.ttf'));
        $this->assertStringContainsString('STF-TEST-001', $html);
    }

    public function test_staff_id_card_returns_403_without_print_permission(): void
    {
        Permission::firstOrCreate(['name' => 'print_staff_id', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'View Staff', 'guard_name' => 'web']);

        $branch = BranchFactory::new()->create();
        Context::add('current_branch_id', $branch->id);

        $staff = Staff::factory()->create(['branch_id' => $branch->id]);

        $user = User::factory()->create();
        $user->givePermissionTo('View Staff');

        $response = $this->actingAs($user)->get(route('staff.id-card', $staff));

        $response->assertForbidden();
    }

    public function test_staff_id_card_returns_404_when_the_feature_is_disabled(): void
    {
        FeatureSettings::fake(['staff_id_card_enabled' => false]);
        Permission::firstOrCreate(['name' => 'print_staff_id', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'View Staff', 'guard_name' => 'web']);

        $branch = BranchFactory::new()->create();
        Context::add('current_branch_id', $branch->id);
        $staff = Staff::factory()->create(['branch_id' => $branch->id]);
        $user = User::factory()->create();
        $user->givePermissionTo(['print_staff_id', 'View Staff']);

        $this->actingAs($user)->get(route('staff.id-card', $staff))->assertNotFound();
    }

    public function test_bulk_staff_id_cards_print_every_selected_staff_member_on_one_sheet(): void
    {
        Permission::firstOrCreate(['name' => 'print_staff_id', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'View Staff', 'guard_name' => 'web']);

        $branch = BranchFactory::new()->create();
        Context::add('current_branch_id', $branch->id);

        $staff = collect(['STF-BULK-001', 'STF-BULK-002'])->map(fn (string $number) => Staff::factory()->create([
            'branch_id' => $branch->id,
            'staff_number' => $number,
        ]));

        $department = Department::factory()->create(['name' => 'Bulk Print Ward']);
        $staff->each(fn (Staff $member) => StaffDepartment::factory()->create([
            'staff_id' => $member->id,
            'department_id' => $department->id,
            'is_primary' => true,
        ]));

        $user = User::factory()->create();
        $user->givePermissionTo(['print_staff_id', 'View Staff']);

        $response = $this->actingAs($user)->get(StaffIdCardsBulkController::urlFor($staff->pluck('id')->all()));

        $response->assertOk();
        $html = (string) $response->getContent();
        $this->assertPrintHtmlIsOffline($html);
        $this->assertStringContainsString('print-sheet', $html);
        $this->assertSame(2, substr_count($html, 'class="id-card"'));
        $this->assertStringContainsString('STF-BULK-001', $html);
        $this->assertStringContainsString('STF-BULK-002', $html);
        $this->assertSame(2, substr_count($html, 'Bulk Print Ward'));
    }

    public function test_bulk_staff_id_cards_are_guarded_like_the_single_card(): void
    {
        Permission::firstOrCreate(['name' => 'print_staff_id', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'View Staff', 'guard_name' => 'web']);

        $branch = BranchFactory::new()->create();
        Context::add('current_branch_id', $branch->id);
        $staff = Staff::factory()->create(['branch_id' => $branch->id]);
        $url = StaffIdCardsBulkController::urlFor([$staff->getKey()]);

        $viewerOnly = User::factory()->create();
        $viewerOnly->givePermissionTo('View Staff');
        $this->actingAs($viewerOnly)->get($url)->assertForbidden();

        $printerWithoutView = User::factory()->create();
        $printerWithoutView->givePermissionTo('print_staff_id');
        $this->actingAs($printerWithoutView)->get($url)->assertForbidden();

        $user = User::factory()->create();
        $user->givePermissionTo(['print_staff_id', 'View Staff']);

        $tooMany = array_map(fn (int $i): string => 'id-'.$i, range(0, StaffIdCardsBulkController::MAX_CARDS));
        $this->actingAs($user)->get(StaffIdCardsBulkController::urlFor($tooMany))->assertStatus(422);

        FeatureSettings::fake(['staff_id_card_enabled' => false]);
        $this->actingAs($user)->get($url)->assertNotFound();
    }
}
