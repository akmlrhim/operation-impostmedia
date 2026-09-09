<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\LoginWithGoogle;
use App\Exceptions\GoogleLoginDenied;
use App\Http\Controllers\Controller;
use App\Models\GoogleAccount;
use App\Support\Cloudflare\Turnstile;
use App\Support\Google\GoogleOAuth;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Socialite\Two\InvalidStateException;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirect;
use Throwable;

class GoogleAuthController extends Controller
{
    private const HINT_COOKIE = 'google_account_hint';

    public function create(Request $request): Response
    {
        return Inertia::render('auth/login', [
            'status' => $request->session()->get('status'),
            'turnstileSiteKey' => Turnstile::enabled()
                ? config('services.turnstile.site_key')
                : null,
            'knownAccount' => $this->hint($request) !== null,
        ]);
    }

    public function redirect(Request $request): SymfonyRedirect
    {
        $hint = $request->boolean('switch') ? null : $this->hint($request);

        return GoogleOAuth::provider()
            ->scopes(config('services.google.scopes'))
            ->with($this->authParameters($hint))
            ->redirect();
    }

    /**
     * Layar persetujuan cuma dipaksa selama kita belum pegang refresh token
     * akun itu. Tanpa paksaan itu Google tidak pernah mengirim token offline,
     * dan sinkronisasi Kalender berhenti jalan begitu access token kedaluwarsa.
     *
     * @return array<string, string>
     */
    private function authParameters(?string $hint): array
    {
        if ($hint === null || ! $this->hasOfflineAccess($hint)) {
            return ['access_type' => 'offline', 'prompt' => 'consent select_account'];
        }

        return ['access_type' => 'offline', 'login_hint' => $hint];
    }

    private function hasOfflineAccess(string $email): bool
    {
        return GoogleAccount::query()
            ->where('email', $email)
            ->whereNotNull('refresh_token')
            ->exists();
    }

    private function hint(Request $request): ?string
    {
        $value = $request->cookie(self::HINT_COOKIE);

        return is_string($value) && $value !== '' ? $value : null;
    }

    public function callback(Request $request, LoginWithGoogle $login): RedirectResponse
    {
        if ($request->filled('error')) {
            return $this->back('Login dibatalkan.');
        }

        try {
            $googleUser = GoogleOAuth::user();
        } catch (InvalidStateException) {
            return $this->back('Sesi login kedaluwarsa. Silakan coba lagi.');
        } catch (Throwable $e) {
            report($e);

            return $this->back('Tidak bisa menghubungi Google. Coba beberapa saat lagi.');
        }

        try {
            $user = $login->handle($googleUser);
        } catch (GoogleLoginDenied $e) {
            return $this->back($e->getMessage());
        }

        Auth::login($user, remember: true);

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'))->withCookie(
            cookie()->forever(self::HINT_COOKIE, (string) $googleUser->getEmail()),
        );
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    private function back(string $message): RedirectResponse
    {
        return to_route('login')->withErrors(['google' => $message]);
    }
}
