<?php

declare(strict_types=1);

use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Modules\Core\Models\Branch;
use Modules\Core\Settings\SecuritySettings;
use Modules\Patient\Enums\Gender;
use Modules\Staff\Enums\EmploymentStatus;
use Modules\Staff\Enums\StaffType;
use Modules\Staff\Filament\Clusters\StaffCluster\Resources\Staff\Pages\CreateStaff;
use Modules\Staff\Filament\Clusters\StaffCluster\Resources\Staff\Pages\EditStaff;
use Modules\Staff\Filament\Clusters\StaffCluster\Resources\Staff\Pages\ListStaff;
use Modules\Staff\Filament\Clusters\StaffCluster\Resources\Staff\Pages\ViewStaff;
use Modules\Staff\Models\Staff;
use Modules\Staff\Notifications\StaffCredentialsNotification;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

uses(TestCase::class, DatabaseTransactions::class);

beforeEach(function (): void {
    $this->requireModule('Staff');
    $this->migrateModules(['Core', 'Staff']);
    $this->branch = Branch::factory()->create();
    $this->setCurrentBranch($this->branch);

    foreach (['super_admin', 'doctor', 'nurse'] as $role) {
        Role::findOrCreate($role, 'web');
    }

    foreach (['ViewAny Staff', 'View Staff', 'Create Staff', 'Update Staff', 'Create User', 'Update User', 'impersonate_users'] as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $this->admin = User::factory()->create(['is_active' => true]);
    $this->admin->assignRole('super_admin');

    Filament::setCurrentPanel(Filament::getDefaultPanel());
    Notification::fake();
});

function staffFormData(array $overrides = []): array
{
    return array_replace_recursive([
        'branch_id' => test()->branch->id,
        'first_name' => 'Ama',
        'last_name' => 'Mensah',
        'gender' => Gender::FEMALE->value,
        'staff_type' => StaffType::default()->value,
        'employment_status' => EmploymentStatus::default()->value,
        // The world reference tables are empty in tests, so the country/region selects must stay blank.
        'address' => ['country' => null, 'region' => null],
    ], $overrides);
}

function staffWithAccount(array $userAttributes = [], array $roles = ['nurse']): Staff
{
    $user = User::factory()->create([
        'is_active' => true,
        'username' => 'user.'.fake()->unique()->numerify('#####'),
        ...$userAttributes,
    ]);
    $user->syncRoles($roles);

    return blankStaff(['user_id' => $user->id]);
}

/**
 * Staff without address/emergency data: the form's world-table and relationship selects
 * reject factory values when the reference tables are empty.
 */
function blankStaff(array $attributes = []): Staff
{
    return Staff::factory()->create([
        'branch_id' => test()->branch->id,
        'address' => null,
        'emergency_contact' => null,
        ...$attributes,
    ]);
}

it('creates staff without a login account when access is off', function (): void {
    Livewire::actingAs($this->admin)
        ->test(CreateStaff::class)
        ->fillForm(staffFormData(['account' => ['login_access' => false]]))
        ->call('create')
        ->assertHasNoFormErrors();

    $staff = Staff::query()->where('first_name', 'Ama')->where('last_name', 'Mensah')->firstOrFail();

    expect($staff->user_id)->toBeNull();
});

it('creates the login account with the credentials set on the staff form', function (): void {
    Livewire::actingAs($this->admin)
        ->test(CreateStaff::class)
        ->fillForm(staffFormData(['account' => [
            'login_access' => true,
            'username' => 'ama.mensah',
            'email' => 'ama.mensah@example.test',
            'roles' => ['nurse'],
            'password' => 'Secret-pass-123',
            'password_confirmation' => 'Secret-pass-123',
            'send_credentials' => false,
        ]]))
        ->call('create')
        ->assertHasNoFormErrors();

    $staff = Staff::query()->where('first_name', 'Ama')->where('last_name', 'Mensah')->firstOrFail();
    $user = $staff->user;

    expect($user)->not->toBeNull()
        ->and($user->username)->toBe('ama.mensah')
        ->and($user->email)->toBe('ama.mensah@example.test')
        ->and($user->is_active)->toBeTrue()
        ->and(Hash::check('Secret-pass-123', $user->password))->toBeTrue()
        ->and($user->hasRole('nurse'))->toBeTrue();

    Notification::assertNotSentTo($user, StaffCredentialsNotification::class);
});

it('generates username, email and password and emails them when left empty', function (): void {
    Livewire::actingAs($this->admin)
        ->test(CreateStaff::class)
        ->fillForm(staffFormData(['account' => [
            'login_access' => true,
            'roles' => ['doctor'],
            'send_credentials' => true,
        ]]))
        ->call('create')
        ->assertHasNoFormErrors();

    $user = Staff::query()->where('first_name', 'Ama')->where('last_name', 'Mensah')->firstOrFail()->user;

    expect($user)->not->toBeNull()
        ->and($user->username)->toStartWith('ama.mensah')
        ->and($user->email)->toStartWith('ama.mensah')
        ->and($user->hasRole('doctor'))->toBeTrue();

    Notification::assertSentTo($user, StaffCredentialsNotification::class);
});

it('requires a password when credentials are not emailed', function (): void {
    Livewire::actingAs($this->admin)
        ->test(CreateStaff::class)
        ->fillForm(staffFormData(['account' => [
            'login_access' => true,
            'roles' => ['doctor'],
            'send_credentials' => false,
        ]]))
        ->call('create')
        ->assertHasFormErrors(['account.password' => 'required']);
});

it('rejects a username that another user already has', function (): void {
    User::factory()->create(['username' => 'taken.name']);

    Livewire::actingAs($this->admin)
        ->test(CreateStaff::class)
        ->fillForm(staffFormData(['account' => [
            'login_access' => true,
            'username' => 'taken.name',
            'roles' => ['doctor'],
            'send_credentials' => true,
        ]]))
        ->call('create')
        ->assertHasFormErrors(['account.username' => 'unique']);
});

it('fills the account on edit and keeps the password when it is left empty', function (): void {
    $staff = staffWithAccount(['username' => 'kofi.boateng', 'email' => 'kofi@example.test', 'password' => Hash::make('Original-pass-1')]);

    Livewire::actingAs($this->admin)
        ->test(EditStaff::class, ['record' => $staff->getRouteKey()])
        ->assertSchemaStateSet([
            'account.login_access' => true,
            'account.username' => 'kofi.boateng',
            'account.email' => 'kofi@example.test',
            'account.roles' => ['nurse'],
        ])
        ->fillForm(['account' => ['email' => 'kofi.new@example.test', 'roles' => ['doctor']]])
        ->call('save')
        ->assertHasNoFormErrors();

    $user = $staff->user->refresh();

    expect($user->email)->toBe('kofi.new@example.test')
        ->and($user->username)->toBe('kofi.boateng')
        ->and(Hash::check('Original-pass-1', $user->password))->toBeTrue()
        ->and($user->getRoleNames()->all())->toBe(['doctor']);
});

it('changes the password on edit when a new one is entered', function (): void {
    $staff = staffWithAccount(['password' => Hash::make('Original-pass-1')]);

    Livewire::actingAs($this->admin)
        ->test(EditStaff::class, ['record' => $staff->getRouteKey()])
        ->fillForm(['account' => ['password' => 'Brand-new-pass-2', 'password_confirmation' => 'Brand-new-pass-2']])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(Hash::check('Brand-new-pass-2', $staff->user->refresh()->password))->toBeTrue();
});

it('adds a login account to existing staff from the edit form', function (): void {
    $staff = blankStaff(['first_name' => 'Yaw', 'last_name' => 'Asante']);

    Livewire::actingAs($this->admin)
        ->test(EditStaff::class, ['record' => $staff->getRouteKey()])
        ->assertSchemaStateSet(['account.login_access' => false])
        ->fillForm(['account' => [
            'login_access' => true,
            'username' => 'yaw.asante',
            'roles' => ['doctor'],
            'send_credentials' => true,
        ]])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($staff->refresh()->user?->username)->toBe('yaw.asante');
});

it('deactivates the account without deleting it when access is switched off', function (): void {
    $staff = staffWithAccount();
    $userId = $staff->user_id;

    Livewire::actingAs($this->admin)
        ->test(EditStaff::class, ['record' => $staff->getRouteKey()])
        ->fillForm(['account' => ['login_access' => false]])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($staff->refresh()->user_id)->toBe($userId)
        ->and($staff->user->is_active)->toBeFalse();
});

it('hides the login step from users who cannot manage user accounts', function (): void {
    $clerk = User::factory()->create(['is_active' => true]);
    $clerk->givePermissionTo(['ViewAny Staff', 'View Staff', 'Create Staff']);

    Livewire::actingAs($clerk)
        ->test(CreateStaff::class)
        ->fillForm(staffFormData())
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Staff::query()->where('first_name', 'Ama')->firstOrFail()->user_id)->toBeNull();
});

it('only offers the super administrator role to super administrators', function (): void {
    $manager = User::factory()->create(['is_active' => true]);
    $manager->givePermissionTo(['ViewAny Staff', 'View Staff', 'Create Staff', 'Create User']);

    Livewire::actingAs($manager)
        ->test(CreateStaff::class)
        ->fillForm(staffFormData(['account' => [
            'login_access' => true,
            'roles' => ['super_admin'],
            'send_credentials' => true,
        ]]))
        ->call('create')
        ->assertHasFormErrors(['account.roles.0']);
});

it('impersonates a staff member from the view page and the staff table', function (): void {
    $staff = staffWithAccount();

    Livewire::actingAs($this->admin)
        ->test(ViewStaff::class, ['record' => $staff->getRouteKey()])
        ->assertActionVisible('impersonate');

    Livewire::actingAs($this->admin)
        ->test(ListStaff::class)
        ->assertActionVisible(TestAction::make('impersonate')->table($staff))
        ->callAction(TestAction::make('impersonate')->table($staff));

    expect(auth()->id())->toBe($staff->user_id);
});

it('hides impersonation for staff without an active account', function (): void {
    $withoutAccount = blankStaff();
    $inactive = staffWithAccount(['is_active' => false]);

    Livewire::actingAs($this->admin)
        ->test(ListStaff::class)
        ->assertActionHidden(TestAction::make('impersonate')->table($withoutAccount))
        ->assertActionHidden(TestAction::make('impersonate')->table($inactive));
});

it('hides impersonation when the security setting is off', function (): void {
    $settings = app(SecuritySettings::class);
    $settings->impersonation_enabled = false;
    $settings->save();

    $staff = staffWithAccount();

    Livewire::actingAs($this->admin)
        ->test(ViewStaff::class, ['record' => $staff->getRouteKey()])
        ->assertActionHidden('impersonate');
});

it('requires the impersonate permission and protects super administrators', function (): void {
    $viewer = User::factory()->create(['is_active' => true]);
    $viewer->givePermissionTo(['ViewAny Staff', 'View Staff']);

    $nurse = staffWithAccount();
    $superAdmin = staffWithAccount(roles: ['super_admin']);

    Livewire::actingAs($viewer)
        ->test(ViewStaff::class, ['record' => $nurse->getRouteKey()])
        ->assertActionHidden('impersonate');

    $viewer->givePermissionTo('impersonate_users');

    Livewire::actingAs($viewer->refresh())
        ->test(ViewStaff::class, ['record' => $nurse->getRouteKey()])
        ->assertActionVisible('impersonate');

    Livewire::actingAs($viewer)
        ->test(ViewStaff::class, ['record' => $superAdmin->getRouteKey()])
        ->assertActionHidden('impersonate');
});

it('offers a print sheet link for the selected staff', function (): void {
    $first = blankStaff();
    $second = blankStaff();

    Livewire::actingAs($this->admin)
        ->test(ListStaff::class)
        ->selectTableRecords([$first->getKey(), $second->getKey()])
        ->mountAction(TestAction::make('print_id_cards')->table()->bulk())
        ->assertMountedActionModalSee('Open print sheet')
        ->assertMountedActionModalSeeHtml(['staff/id-cards?ids', $first->getKey(), $second->getKey()]);
});

it('lists staff with login accounts without lazy loading their users', function (): void {
    staffWithAccount();
    staffWithAccount(roles: ['doctor']);
    staffWithAccount(roles: ['super_admin']);
    blankStaff();

    Model::preventLazyLoading();

    try {
        Livewire::actingAs($this->admin)
            ->test(ListStaff::class)
            ->assertOk();
    } finally {
        Model::preventLazyLoading(false);
    }
});
