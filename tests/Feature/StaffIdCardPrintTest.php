<?php

namespace Modules\Staff\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Context;
use Modules\Core\Database\Factories\BranchFactory;
use Modules\Core\Tests\Support\AssertsOfflinePrintHtml;
use Modules\Staff\Models\Staff;
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
}
