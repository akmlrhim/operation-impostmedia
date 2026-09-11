<?php

namespace Tests\Feature\Security;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_web_response_carries_the_hardening_headers(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk();
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Cross-Origin-Opener-Policy', 'same-origin');
        $response->assertHeaderMissing('X-Powered-By');
    }

    public function test_the_content_security_policy_locks_down_framing_and_inline_scripts(): void
    {
        $policy = $this->get(route('login'))->headers->get('Content-Security-Policy');

        $this->assertIsString($policy);
        $this->assertStringContainsString("frame-ancestors 'none'", $policy);
        $this->assertStringContainsString("object-src 'none'", $policy);
        $this->assertStringContainsString("base-uri 'self'", $policy);
        $this->assertStringContainsString("form-action 'self'", $policy);
        $this->assertMatchesRegularExpression("/script-src [^;]*'nonce-[A-Za-z0-9+\/=]+'/", $policy);
        $this->assertStringNotContainsString("script-src 'self' 'unsafe-inline'", $policy);
    }

    public function test_form_action_allows_the_host_google_sign_in_actually_redirects_to(): void
    {
        config(['services.turnstile.enabled' => false]);

        $location = (string) $this->post(route('google.redirect'))->headers->get('Location');
        $parts = parse_url($location);

        $this->assertArrayHasKey('host', $parts, "Rute google.redirect tidak mengarah ke mana pun: {$location}");

        $origin = $parts['scheme'].'://'.$parts['host'];
        $policy = (string) $this->get(route('login'))->headers->get('Content-Security-Policy');

        preg_match('/form-action ([^;]*)/', $policy, $matches);

        $this->assertNotEmpty($matches, 'CSP tidak memuat form-action.');
        $this->assertStringContainsString(
            $origin,
            $matches[1],
            "Chromium menerapkan form-action pada tujuan redirect, jadi {$origin} harus disebut atau tombol Google diblokir.",
        );
    }

    public function test_the_inline_appearance_script_is_served_with_a_nonce(): void
    {
        $response = $this->get(route('login'));
        $policy = (string) $response->headers->get('Content-Security-Policy');

        preg_match("/'nonce-([A-Za-z0-9+\/=]+)'/", $policy, $matches);

        $this->assertNotEmpty($matches, 'CSP tidak memuat nonce.');
        $response->assertSee('nonce="'.$matches[1].'"', escape: false);
    }

    public function test_search_engines_are_told_not_to_index_anything(): void
    {
        $this->get(route('login'))->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive, nosnippet, noimageindex');
        $this->get(route('login'))->assertSee('<meta name="robots" content="noindex, nofollow">', escape: false);

        $this->get(route('login'))->assertHeader(
            'X-Robots-Tag',
            'noindex, nofollow, noarchive, nosnippet, noimageindex',
        );
    }

    public function test_robots_txt_does_not_block_crawling_so_the_noindex_rule_is_readable(): void
    {
        $robots = (string) file_get_contents(public_path('robots.txt'));

        $this->assertStringNotContainsString('Disallow: /', $robots);
    }

    /**
     * Tata bahasa host-source CSP tidak mengenal alamat IPv6 literal. Kalau
     * Vite menulis `http://[::1]:5173` ke public/hot dan itu diteruskan apa
     * adanya, browser membuang seluruh direktifnya dan aset dev ikut diblokir.
     */
    public function test_an_ipv6_vite_host_is_rewritten_to_localhost(): void
    {
        $hot = public_path('hot');
        $original = file_exists($hot) ? file_get_contents($hot) : null;

        try {
            file_put_contents($hot, 'http://[::1]:5173');

            $policy = (string) $this->get(route('login'))->headers->get('Content-Security-Policy');

            $this->assertStringNotContainsString('[::1]', $policy);
            $this->assertStringContainsString('http://localhost:5173', $policy);
            $this->assertStringContainsString('ws://localhost:5173', $policy);
        } finally {
            $original === null ? @unlink($hot) : file_put_contents($hot, $original);
        }
    }

    /**
     * Vite dibiarkan `http://127.0.0.1:5173` apa adanya — browser mem-bandingkan
     * CSP dengan URL yang di-fetch, jadi host harus persis, bukan `localhost`.
     * IPv4 literal sah sebagai host-source CSP.
     */
    public function test_an_ipv4_vite_host_is_kept_as_is(): void
    {
        $hot = public_path('hot');
        $original = file_exists($hot) ? file_get_contents($hot) : null;

        try {
            file_put_contents($hot, 'http://127.0.0.1:5173');

            $policy = (string) $this->get(route('login'))->headers->get('Content-Security-Policy');

            $this->assertStringContainsString('http://127.0.0.1:5173', $policy);
            $this->assertStringContainsString('ws://127.0.0.1:5173', $policy);
        } finally {
            $original === null ? @unlink($hot) : file_put_contents($hot, $original);
        }
    }

    /**
     * Noto Sans dimuat dari Google Fonts. Kalau kedua host ini lepas dari CSP,
     * font diblokir tanpa pesan apa pun dan halaman jatuh ke font sistem.
     */
    public function test_the_policy_lets_google_fonts_through(): void
    {
        $policy = (string) $this->get(route('login'))->headers->get('Content-Security-Policy');

        $this->assertMatchesRegularExpression(
            '/style-src [^;]*https:\/\/fonts\.googleapis\.com/',
            $policy,
        );
        $this->assertMatchesRegularExpression(
            '/font-src [^;]*https:\/\/fonts\.gstatic\.com/',
            $policy,
        );
    }

    public function test_the_page_requests_noto_sans_from_google(): void
    {
        $response = $this->get(route('login'));

        $response->assertSee('fonts.googleapis.com/css2?family=Noto+Sans', escape: false);
        $response->assertSee('rel="preconnect" href="https://fonts.gstatic.com"', escape: false);
    }

    public function test_hsts_is_only_sent_over_https(): void
    {
        $this->get(route('login'))->assertHeaderMissing('Strict-Transport-Security');

        $this->get('https://localhost/login')->assertHeader(
            'Strict-Transport-Security',
            'max-age=31536000; includeSubDomains',
        );
    }

    public function test_company_assets_are_no_longer_reachable_without_a_session(): void
    {
        $this->get(route('company.image', ['kind' => 'signature']))->assertRedirect(route('login'));

        $this->actingAs(User::factory()->create())
            ->get(route('company.image', ['kind' => 'signature']))
            ->assertNotFound();
    }
}
