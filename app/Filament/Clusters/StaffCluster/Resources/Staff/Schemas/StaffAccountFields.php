<?php

namespace Modules\Staff\Filament\Clusters\StaffCluster\Resources\Staff\Schemas;

use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Modules\Core\Enums\UserRole;
use Modules\Core\Support\SuperAdmin;
use Modules\Staff\Models\Staff;

/**
 * Login-account fields for a staff member, shared by the staff create/edit form
 * ("Login Access" step) and the account actions on the Staff table and view page,
 * so there is one set of rules for usernames, emails, roles and passwords.
 *
 * State shape: login_access, username, email, roles, password,
 * password_confirmation, send_credentials. Persisted by StaffAccountService::syncAccount().
 */
class StaffAccountFields
{
    /**
     * The "Login Access" section used inside the staff form. State lives under `account`.
     */
    public static function formSection(): Group
    {
        return Group::make([
            Section::make(__('Login Access'))
                ->icon('heroicon-o-key')
                ->description(__('Give this staff member an account to sign in to the system.'))
                ->schema([
                    Toggle::make('login_access')
                        ->label(__('Allow login access'))
                        ->helperText(fn (?Staff $record): ?string => $record?->user_id
                            ? __('Turning this off deactivates the existing account; it is not deleted.')
                            : null)
                        ->live(),
                    Grid::make(2)
                        ->schema(static::credentialFields())
                        ->visible(fn (Get $get): bool => (bool) $get('login_access')),
                ]),
        ])
            ->statePath('account')
            ->visible(fn (?Staff $record): bool => static::canManageAccounts($record));
    }

    /**
     * Fields for the account modals. The toggle is labelled as the account's active state.
     *
     * @return array<int, mixed>
     */
    public static function modalSchema(): array
    {
        return [
            Toggle::make('login_access')
                ->label(__('Account active'))
                ->default(true)
                ->live(),
            Grid::make(2)
                ->schema(static::credentialFields())
                ->visible(fn (Get $get): bool => (bool) $get('login_access')),
        ];
    }

    /**
     * @return array<int, mixed>
     */
    public static function credentialFields(): array
    {
        return [
            TextInput::make('username')
                ->label(__('Username'))
                ->maxLength(255)
                ->regex('/^[a-z0-9._-]+$/')
                ->validationMessages(['regex' => __('Usernames may only contain lowercase letters, numbers, dots, dashes and underscores.')])
                ->unique('users', 'username', ignorable: fn (?Staff $record): ?User => $record?->user, ignoreRecord: false)
                ->required(fn (?Staff $record): bool => static::hasAccount($record))
                ->helperText(fn (?Staff $record): ?string => static::hasAccount($record)
                    ? null
                    : __('Leave empty to generate one from the name.')),
            TextInput::make('email')
                ->label(__('Login email'))
                ->email()
                ->maxLength(255)
                ->unique('users', 'email', ignorable: fn (?Staff $record): ?User => $record?->user, ignoreRecord: false)
                ->required(fn (?Staff $record): bool => static::hasAccount($record))
                ->helperText(fn (?Staff $record): ?string => static::hasAccount($record)
                    ? null
                    : __('Leave empty to generate one from the name.')),
            Select::make('roles')
                ->label(__('Role(s)'))
                ->options(fn (): array => static::roleOptions())
                ->multiple()
                ->searchable()
                ->preload()
                ->required()
                ->columnSpanFull(),
            TextInput::make('password')
                ->label(fn (?Staff $record): string => static::hasAccount($record) ? __('New password') : __('Password'))
                ->password()
                ->revealable()
                ->minLength(8)
                ->maxLength(255)
                ->required(fn (?Staff $record, Get $get): bool => ! static::hasAccount($record) && ! $get('send_credentials'))
                ->helperText(fn (?Staff $record): string => static::hasAccount($record)
                    ? __('Leave empty to keep the current password.')
                    : __('Leave empty to generate one and email it to the staff member.'))
                ->live(onBlur: true),
            TextInput::make('password_confirmation')
                ->label(__('Confirm password'))
                ->password()
                ->revealable()
                ->same('password')
                ->required(fn (Get $get): bool => filled($get('password')))
                ->visible(fn (Get $get): bool => filled($get('password'))),
            Toggle::make('send_credentials')
                ->label(__('Email the login details to the staff member'))
                ->default(fn (?Staff $record): bool => ! static::hasAccount($record))
                ->helperText(fn (?Staff $record): ?string => static::hasAccount($record)
                    ? __('Only sent when a new password is set.')
                    : null)
                ->live()
                ->columnSpanFull(),
        ];
    }

    /**
     * Form state for an existing staff member's account (password fields are never filled).
     *
     * @return array{login_access: bool, username: ?string, email: ?string, roles: array<int, string>, send_credentials: bool}
     */
    public static function stateFor(Staff $staff): array
    {
        $user = $staff->user;

        return [
            'login_access' => (bool) $user?->is_active,
            'username' => $user?->username,
            'email' => $user?->email,
            'roles' => $user?->getRoleNames()->all() ?? [],
            'send_credentials' => false,
        ];
    }

    public static function canManageAccounts(?Staff $staff = null): bool
    {
        if (SuperAdmin::check()) {
            return true;
        }

        if ($staff?->user !== null && SuperAdmin::check($staff->user)) {
            return false;
        }

        return Gate::allows($staff?->user_id ? 'update' : 'create', User::class);
    }

    /**
     * @return array<string, string>
     */
    public static function roleOptions(): array
    {
        return collect(UserRole::cases())
            ->reject(fn (UserRole $role): bool => $role === UserRole::SUPER_ADMIN && ! SuperAdmin::check(Auth::user()))
            ->mapWithKeys(fn (UserRole $role): array => [$role->value => (string) $role->getLabel()])
            ->all();
    }

    protected static function hasAccount(?Staff $staff): bool
    {
        return (bool) $staff?->user_id;
    }
}
