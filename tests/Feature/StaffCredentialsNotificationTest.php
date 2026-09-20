<?php

namespace Modules\Staff\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Core\Settings\NotificationSettings;
use Modules\Staff\Notifications\StaffCredentialsNotification;
use Tests\TestCase;

class StaffCredentialsNotificationTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->migrateModules(['Core', 'Staff']);
    }

    public function test_mail_channel_follows_the_staff_credentials_setting(): void
    {
        $user = User::factory()->create();
        $notification = new StaffCredentialsNotification('secret-123');

        NotificationSettings::fake(['staff_credentials_mail' => true]);
        $this->assertSame(['mail', 'database'], $notification->via($user));

        NotificationSettings::fake(['staff_credentials_mail' => false]);
        $this->assertSame(['database'], $notification->via($user));
    }

    public function test_database_payload_does_not_contain_the_password(): void
    {
        $user = User::factory()->create();
        $payload = (new StaffCredentialsNotification('secret-123'))->toArray($user);

        $this->assertArrayNotHasKey('password', $payload);
        $this->assertStringNotContainsString('secret-123', json_encode($payload));
    }
}
