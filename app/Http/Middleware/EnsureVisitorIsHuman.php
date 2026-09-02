<?php

namespace App\Http\Middleware;

use App\Support\Cloudflare\Turnstile;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureVisitorIsHuman
{
    public function __construct(private readonly Turnstile $turnstile) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! Turnstile::enabled()) {
            return $next($request);
        }

        $token = $request->string('cf-turnstile-response')->toString();

        if (! $this->turnstile->verify($token, $request->ip())) {
            return back()->withErrors([
                'google' => 'Verifikasi keamanan gagal. Muat ulang halaman lalu coba lagi.',
            ]);
        }

        return $next($request);
    }
}
