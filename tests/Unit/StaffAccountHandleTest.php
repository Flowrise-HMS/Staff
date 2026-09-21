<?php

namespace Modules\Staff\Tests\Unit;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Staff\Classes\Services\StaffAccountService;
use Modules\Staff\Models\Staff;
use Tests\TestCase;

/**
 * "UI QA" + "Nurse" used to become the username "ui qa.nurse" (with a space).
 */
class StaffAccountHandleTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->migrateModules(['Core', 'Staff']);
    }

    public function test_handle_collapses_spaces_and_punctuation_to_dots(): void
    {
        $this->assertSame('ui.qa.nurse', StaffAccountService::handleFor('UI QA', 'Nurse'));
        $this->assertSame('ama.serwaa.osei-bonsu', StaffAccountService::handleFor(' Ama  Serwaa ', 'Osei-Bonsu'));
        $this->assertSame('jose.nunez', StaffAccountService::handleFor('José', 'Núñez'));
        $this->assertSame('user', StaffAccountService::handleFor(null, '!!!'));
    }

    public function test_generated_username_and_email_have_no_spaces_and_stay_unique(): void
    {
        $service = app(StaffAccountService::class);
        $staff = Staff::factory()->create(['first_name' => 'UI QA', 'last_name' => 'Nurse']);

        $this->assertSame('ui.qa.nurse', $service->generateUsername($staff));
        $this->assertStringStartsWith('ui.qa.nurse@', $service->generateEmail($staff));

        User::factory()->create(['username' => 'ui.qa.nurse']);

        $this->assertSame('ui.qa.nurse1', $service->generateUsername($staff));
    }
}
