<?php

namespace Modules\Staff\Classes\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Modules\Staff\Models\Staff;
use Modules\Staff\Notifications\StaffCredentialsNotification;

class StaffAccountService
{
    public function __construct(
        protected ?string $defaultRole = null
    ) {}

    public function createUserAccount(Staff $staff, array $data = []): ?User
    {
        if ($staff->user_id) {
            return $staff->user;
        }

        $username = $data['username'] ?? $this->generateUsername($staff);
        $email = $data['email'] ?? $this->generateEmail($staff);
        $password = $data['password'] ?? $this->generatePassword();

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
            $user->assignRole($this->defaultRole);
        }

        if ($data['send_credentials'] ?? false) {
            $this->sendCredentialsEmail($user, $password);
        }

        return $user;
    }

    public function generateEmail(Staff $staff): string
    {
        $baseEmail = strtolower($staff->first_name.'.'.$staff->last_name);
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
        $baseUsername = strtolower($staff->first_name.'.'.$staff->last_name);
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
