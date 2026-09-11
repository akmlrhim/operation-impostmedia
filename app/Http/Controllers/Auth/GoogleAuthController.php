<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\LoginWithGoogle;
use App\Exceptions\GoogleLoginDenied;
use App\Http\Controllers\Controller;
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
        ]);
    }

    public function redirect(Request $request): SymfonyRedirect
    {
        return GoogleOAuth::provider()
            ->scopes(config('services.google.scopes'))
            ->with($this->authParameters())
            ->redirect();
    }

    /**
     * Selalu tampilkan pemilih akun Google (select_account) setiap login,
     * sehingga pengguna bisa memilih akun mana yang akan dipakai.
     *
     * @return array<string, string>
     */
    private function authParameters(): array
    {
        return [
            'access_type' => 'offline',
            'prompt' => 'consent select_account',
        ];
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
