<?php

namespace Modules\Staff\Classes\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Modules\Staff\Models\Staff;
use Modules\Staff\Notifications\StaffCredentialsNotification;

class StaffAccountService
{
    public function __construct(
        protected string|array|null $defaultRole = null
    ) {}

    public function setRole(array|string|null $roles)
    {
        $this->defaultRole = $roles;

        return $this;
    }

    public function createUserAccount(Staff $staff, array $data = []): ?User
    {
        if ($staff->user_id) {
            return $staff->user;
        }

        $username = isset($data['username']) && ! empty($data['username']) ? $data['username'] : $this->generateUsername($staff);
        $email = isset($data['email']) && ! empty($data['email']) ? $data['email'] : $this->generateEmail($staff);
        $password = filled($data['password'] ?? null) ? $data['password'] : $this->generatePassword();

        $user = User::create([
            'name' => $staff->full_name,
            'email' => $email,
            'username' => $username,
            'password' => Hash::make($password),
            'phone' => $staff->getPhone(),
            'is_active' => true,
        ]);

        $staff->update(['user_id' => $user->id]);

        if ($this->defaultRole) {
            $user->syncRoles($this->defaultRole);
        }

        if ($data['send_credentials'] ?? false) {
            $this->sendCredentialsEmail($user, $password);
        }

        return $user;
    }

    /**
     * Create, update or deactivate a staff member's login account from the
     * StaffAccountFields state (login_access, username, email, roles, password,
     * send_credentials). Turning access off deactivates the user; it is never
     * deleted or unlinked, so the audit trail stays intact.
     *
     * @param  array<string, mixed>  $data
     */
    public function syncAccount(Staff $staff, array $data): ?User
    {
        $user = $staff->user;

        if (! ($data['login_access'] ?? false)) {
            if ($user !== null && $user->is_active) {
                $this->deactivateAccount($staff);
            }

            return $user?->refresh();
        }

        $roles = array_values(array_filter((array) ($data['roles'] ?? [])));

        if ($user === null) {
            $this->setRole($roles !== [] ? $roles : $this->defaultRole);

            return $this->createUserAccount($staff, $data);
        }

        $user->fill(array_filter([
            'name' => $staff->full_name,
            'username' => $data['username'] ?? null,
            'email' => $data['email'] ?? null,
        ], fn (mixed $value): bool => filled($value)));
        $user->is_active = true;

        $newPassword = filled($data['password'] ?? null) ? (string) $data['password'] : null;

        if ($newPassword !== null) {
            $user->password = Hash::make($newPassword);
        }

        $user->save();

        if ($roles !== []) {
            $user->syncRoles($roles);
        }

        if ($newPassword !== null && ($data['send_credentials'] ?? false)) {
            $this->sendCredentialsEmail($user, $newPassword);
        }

        return $user;
    }

    /**
     * Login handle derived from a person's names: lowercase ASCII, with spaces
     * and punctuation collapsed to single dots ("Ama Serwaa" + "Osei-Bonsu"
     * becomes "ama.serwaa.osei-bonsu"). Also the local part of generated emails.
     */
    public static function handleFor(?string $firstName, ?string $lastName): string
    {
        $handle = Str::of(Str::ascii(trim(($firstName ?? '').' '.($lastName ?? ''))))
            ->lower()
            ->replaceMatches('/[^a-z0-9-]+/', '.')
            ->replaceMatches('/\.{2,}/', '.')
            ->trim('.-')
            ->toString();

        return $handle !== '' ? $handle : 'user';
    }

    public function generateEmail(Staff $staff): string
    {
        $baseEmail = self::handleFor($staff->first_name, $staff->last_name);
        $email = $baseEmail.'@'.config('mail.domain', 'hospital.com');

        $counter = 1;
        while (User::where('email', $email)->exists()) {
            $email = $baseEmail.'.'.$counter.'@'.config('mail.domain', 'hospital.com');
            $counter++;
        }

        return $email;
    }

    public function generatePassword(int $length = 12): string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
        $password = '';

        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, strlen($chars) - 1)];
        }

        return $password;
    }

    public function generateUsername(Staff $staff): string
    {
        $baseUsername = self::handleFor($staff->first_name, $staff->last_name);
        $username = $baseUsername;

        $counter = 1;
        while (User::where('username', $username)->exists()) {
            $username = $baseUsername.$counter;
            $counter++;
        }

        return $username;
    }

    public function sendCredentialsEmail(User $user, string $plainPassword): void
    {
        $user->notify(new StaffCredentialsNotification(
            $plainPassword
        ));
    }

    public function resendCredentials(Staff $staff): ?bool
    {
        if (! $staff->user) {
            return null;
        }

        $password = $this->generatePassword();
        $staff->user->update(['password' => Hash::make($password)]);
        $this->sendCredentialsEmail($staff->user, $password);

        return true;
    }

    public function resetPassword(Staff $staff): ?string
    {
        if (! $staff->user) {
            return null;
        }

        $password = $this->generatePassword();
        $staff->user->update(['password' => Hash::make($password)]);
        $this->sendCredentialsEmail($staff->user, $password);

        return $password;
    }

    public function deactivateAccount(Staff $staff): bool
    {
        if (! $staff->user) {
            return false;
        }

        $staff->user->update(['is_active' => false]);

        return true;
    }

    public function activateAccount(Staff $staff): bool
    {
        if (! $staff->user) {
            return false;
        }

        $staff->user->update(['is_active' => true]);

        return true;
    }

    public function unlinkAccount(Staff $staff): bool
    {
        if (! $staff->user_id) {
            return false;
        }

        $staff->update(['user_id' => null]);

        return true;
    }

    public function validateCredentials(array $credentials): bool
    {
        $validator = Validator::make($credentials, [
            'email' => ['required', 'email'],
            'password' => ['required', 'min:8'],
        ]);

        return ! $validator->fails();
    }

    public function getAccountStatus(Staff $staff): array
    {
        $user = $staff->user;

        if (! $user) {
            return [
                'has_account' => false,
                'is_active' => false,
                'email' => null,
                'last_login' => null,
            ];
        }

        return [
            'has_account' => true,
            'is_active' => $user->is_active,
            'email' => $user->email,
            'created_at' => $user->created_at,
            'email_verified_at' => $user->email_verified_at,
            'roles' => $user->getRoleNames(),
        ];
    }
}
