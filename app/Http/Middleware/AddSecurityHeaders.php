<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

class AddSecurityHeaders
{
    private const PERMISSIONS = 'accelerometer=(), autoplay=(), camera=(), display-capture=(), encrypted-media=(), geolocation=(), gyroscope=(), magnetometer=(), microphone=(), midi=(), payment=(), usb=()';

    /**
     * CRM internal, tidak pernah boleh muncul di mesin pencari. Dikirim sebagai
     * header supaya berlaku juga untuk unduhan PDF dan respons non-HTML.
     */
    private const ROBOTS = 'noindex, nofollow, noarchive, nosnippet, noimageindex';

    /**
     * Noto Sans diambil dari Google Fonts: lembar gayanya dilayani
     * fonts.googleapis.com, berkas woff2-nya dari fonts.gstatic.com. Keduanya
     * harus disebut, kalau tidak font diblokir CSP dan halaman diam-diam
     * jatuh kembali ke font bawaan sistem.
     */
    private const FONT_STYLESHEET = 'https://fonts.googleapis.com';

    private const FONT_FILES = 'https://fonts.gstatic.com';

    /**
     * Tujuan OAuth Google. Tombol "Lanjutkan dengan Google" mengirim form ke
     * rute sendiri, lalu rute itu membalas 302 ke accounts.google.com. Chromium
     * menerapkan `form-action` pada tujuan redirect juga, bukan hanya pada
     * action awalnya, jadi tanpa baris ini submit-nya diblokir dengan pesan
     * yang menunjuk URL lokal dan menyesatkan.
     */
    private const GOOGLE_OAUTH = 'https://accounts.google.com';

    public function handle(Request $request, Closure $next): Response
    {
        Vite::useCspNonce();

        $response = $next($request);
        $headers = $response->headers;

        $headers->set('X-Robots-Tag', self::ROBOTS);
        $headers->set('X-Content-Type-Options', 'nosniff');
        $headers->set('X-Frame-Options', 'DENY');
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $headers->set('Permissions-Policy', self::PERMISSIONS);
        $headers->set('Cross-Origin-Opener-Policy', 'same-origin');
        $headers->set('Cross-Origin-Resource-Policy', 'same-origin');
        $headers->set('X-Permitted-Cross-Domain-Policies', 'none');
        $headers->set('Content-Security-Policy', $this->policy());
        $headers->remove('X-Powered-By');

        if ($request->secure()) {
            $headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }

    private function policy(): string
    {
        $dev = $this->devServer();

        $script = ["'self'", "'nonce-".Vite::cspNonce()."'", 'https://challenges.cloudflare.com'];
        $style = ["'self'", "'unsafe-inline'", self::FONT_STYLESHEET];
        $connect = ["'self'", 'https://*.pusher.com', 'wss://*.pusher.com'];

        if ($dev !== null) {
            $script[] = $dev;
            $script[] = "'unsafe-eval'";
            $style[] = $dev;
            $connect[] = $dev;
            $connect[] = (string) preg_replace('#^http#', 'ws', $dev);
        }

        return $this->render([
            'default-src' => ["'self'"],
            'base-uri' => ["'self'"],
            'form-action' => ["'self'", self::GOOGLE_OAUTH],
            'frame-ancestors' => ["'none'"],
            'object-src' => ["'none'"],
            'img-src' => ["'self'", 'data:', 'blob:', 'https:'],
            'font-src' => ["'self'", 'data:', self::FONT_FILES],
            'media-src' => ["'self'", 'data:', 'blob:'],
            'worker-src' => ["'self'", 'blob:'],
            'manifest-src' => ["'self'"],
            'frame-src' => ['https://challenges.cloudflare.com'],
            'style-src' => $style,
            'script-src' => $script,
            'connect-src' => $connect,
        ]);
    }

    /**
     * Origin Vite dev server, dinormalkan supaya sah sebagai sumber CSP.
     * Browser membandingkan CSP dengan URL yang benar-benar di-fetch, jadi
     * host-nya harus sama persis. `127.0.0.1` dipakai apa adanya (IPv4 literal
     * sah sebagai host-source), sedangkan `::1` ditulis `localhost` karena
     * tata bahasa host-source tidak mengenal alamat IPv6 literal — kalau lewat
     * begitu saja, browser membuang seluruh direktifnya dan aset dev diblokir.
     */
    private function devServer(): ?string
    {
        if (! Vite::isRunningHot()) {
            return null;
        }

        $origin = rtrim(trim((string) @file_get_contents(public_path('hot'))), '/');
        $parts = parse_url($origin);

        if ($origin === '' || ! isset($parts['scheme'], $parts['host'])) {
            return null;
        }

        $host = trim($parts['host'], '[]');
        $port = isset($parts['port']) ? ':'.$parts['port'] : '';

        if ($host === '::1' || $host === '0.0.0.0') {
            return $parts['scheme'].'://localhost'.$port;
        }

        return str_contains($host, ':') ? null : $parts['scheme'].'://'.$host.$port;
    }

    /**
     * @param  array<string, array<int, string>>  $directives
     */
    private function render(array $directives): string
    {
        $parts = [];

        foreach ($directives as $name => $values) {
            $parts[] = $name.' '.implode(' ', array_unique($values));
        }

        return implode('; ', $parts);
    }
}
