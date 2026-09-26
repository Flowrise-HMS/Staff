<?php

namespace Modules\Staff\Filament\Clusters\StaffCluster\Resources\Staff\Actions;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Modules\Core\Filament\Support\ImpersonateUserAction;
use Modules\Staff\Classes\Services\StaffAccountService;
use Modules\Staff\Filament\Clusters\StaffCluster\Resources\Staff\Schemas\StaffAccountFields;
use Modules\Staff\Models\Staff;
use STS\FilamentImpersonate\Actions\Impersonate;

/**
 * Login-account actions for a staff record, shared by the Staff table and the
 * View/Edit staff pages.
 */
class StaffAccountActions
{
    /**
     * @return array<int, Action>
     */
    public static function all(): array
    {
        return [
            static::impersonate(),
            static::createAccount(),
            static::manageAccount(),
            static::resetPassword(),
            static::resendCredentials(),
        ];
    }

    /**
     * Signs in as the staff member's linked user. Visibility (active account, caller
     * permission, super-admin protection) is decided by the user model's impersonation guards.
     */
    public static function impersonate(): Impersonate
    {
        return ImpersonateUserAction::make(fn (Staff $record): ?User => $record->user);
    }

    public static function createAccount(): Action
    {
        return Action::make('createUserAccount')
            ->label(__('Create User Account'))
            ->icon('heroicon-m-user-plus')
            ->color('success')
            ->visible(fn (Staff $record): bool => ! $record->user_id && StaffAccountFields::canManageAccounts($record))
            ->schema(StaffAccountFields::credentialFields())
            ->action(function (Staff $record, array $data): void {
                $user = app(StaffAccountService::class)->syncAccount($record, [...$data, 'login_access' => true]);

                Notification::make()
                    ->title(__('User account created'))
                    ->body(($data['send_credentials'] ?? false)
                        ? __('Login details have been emailed to :email.', ['email' => $user?->email])
                        : __('Username: :username', ['username' => $user?->username]))
                    ->success()
                    ->send();
            });
    }

    public static function manageAccount(): Action
    {
        return Action::make('manageUserAccount')
            ->label(__('Manage Account'))
            ->icon('heroicon-m-cog-6-tooth')
            ->color('warning')
            ->visible(fn (Staff $record): bool => (bool) $record->user_id && StaffAccountFields::canManageAccounts($record))
            ->fillForm(fn (Staff $record): array => StaffAccountFields::stateFor($record))
            ->schema(StaffAccountFields::modalSchema())
            ->action(function (Staff $record, array $data): void {
                app(StaffAccountService::class)->syncAccount($record, $data);

                Notification::make()
                    ->title(__('Account updated'))
                    ->success()
                    ->send();
            });
    }

    public static function resetPassword(): Action
    {
        return Action::make('resetPassword')
            ->label(__('Reset Password'))
            ->icon('heroicon-m-key')
            ->color('warning')
            ->requiresConfirmation()
            ->visible(fn (Staff $record): bool => (bool) $record->user_id && StaffAccountFields::canManageAccounts($record))
            ->action(function (Staff $record): void {
                app(StaffAccountService::class)->resetPassword($record);

                Notification::make()
                    ->title(__('Password reset'))
                    ->body(__('New credentials sent to :email', ['email' => $record->user?->email]))
                    ->success()
                    ->send();
            });
    }

    public static function resendCredentials(): Action
    {
        return Action::make('resendCredentials')
            ->label(__('Resend Credentials'))
            ->icon('heroicon-m-envelope')
            ->color('info')
            ->requiresConfirmation()
            ->visible(fn (Staff $record): bool => (bool) $record->user_id && StaffAccountFields::canManageAccounts($record))
            ->action(function (Staff $record): void {
                app(StaffAccountService::class)->resendCredentials($record);

                Notification::make()
                    ->title(__('Credentials resent'))
                    ->body(__('Login credentials sent to :email', ['email' => $record->user?->email]))
                    ->success()
                    ->send();
            });
    }
}
