<?php

namespace App\Actions\Auth;

use App\Enums\UserRole;
use App\Exceptions\GoogleLoginDenied;
use App\Models\GoogleAccount;
use App\Models\User;
use App\Support\Crm\Notifier;
use Illuminate\Support\Facades\DB;
use Laravel\Socialite\Two\User as GoogleUser;

class LoginWithGoogle
{
    /**
     * @throws GoogleLoginDenied kalau akunnya tidak berhak masuk
     */
    public function handle(GoogleUser $googleUser): User
    {
        $googleId = (string) $googleUser->getId();
        $email = $googleUser->getEmail();

        if ($email === null || $email === '') {
            throw GoogleLoginDenied::noEmail();
        }

        return DB::transaction(function () use ($googleUser, $googleId, $email): User {
            $account = GoogleAccount::query()->where('google_id', $googleId)->first();

            $user = $account->user
                ?? User::query()->where('email', $email)->first()
                ?? $this->register($googleUser, $email);

            if (! $user->is_active) {
                throw GoogleLoginDenied::inactive();
            }

            $linked = $user->googleAccount;

            if ($linked !== null && $linked->google_id !== $googleId) {
                throw GoogleLoginDenied::linkedToAnotherUser();
            }

            $this->syncUser($user, $googleUser, $email);
            $this->syncAccount($account ?? $linked, $user, $googleUser, $googleId, $email);

            return $user;
        });
    }

    private function register(GoogleUser $googleUser, string $email): User
    {
        $first = ! User::query()->exists();

        $user = new User;

        $user->forceFill([
            'name' => $googleUser->getName() ?: $email,
            'email' => $email,
            'role' => $first ? UserRole::Superuser : UserRole::default(),
            'is_active' => true,
            'approved_at' => $first ? now() : null,
        ])->save();

        if ($user->approved_at === null) {
            Notifier::superusers(
                'user',
                $user->name,
                'Pendaftar baru menunggu persetujuan',
                route('users.index'),
            );
        }

        return $user;
    }

    private function syncUser(User $user, GoogleUser $googleUser, string $email): void
    {
        if ($user->email !== $email && User::query()->where('email', $email)->whereKeyNot($user->id)->exists()) {
            throw GoogleLoginDenied::emailTaken();
        }

        $verified = (bool) ($googleUser->getRaw()['email_verified'] ?? false);

        $user->forceFill([
            'email' => $email,
            'email_verified_at' => $verified
                ? $user->email_verified_at ?? now()
                : $user->email_verified_at,
        ])->save();
    }

    private function syncAccount(
        ?GoogleAccount $account,
        User $user,
        GoogleUser $googleUser,
        string $googleId,
        string $email,
    ): void {
        $account ??= new GoogleAccount;

        $account->forceFill([
            'user_id' => $user->id,
            'google_id' => $googleId,
            'email' => $email,
            'avatar_url' => $googleUser->getAvatar(),
            'access_token' => $googleUser->token,
            'refresh_token' => $googleUser->refreshToken ?: $account->refresh_token,
            'token_expires_at' => $googleUser->expiresIn === null
                ? null
                : now()->addSeconds($googleUser->expiresIn),
            'scopes' => $googleUser->approvedScopes,
            'synced_at' => now(),
        ])->save();

        $user->setRelation('googleAccount', $account);
    }
}
