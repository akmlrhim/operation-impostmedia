<?php

namespace Tests\Feature\Auth;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TurnstileTest extends TestCase
{
    private const VERIFY_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.turnstile.enabled' => true,
            'services.turnstile.secret' => 'secret-uji',
        ]);
    }

    public function test_a_request_without_a_token_never_reaches_google(): void
    {
        Http::fake();

        $this->from(route('login'))
            ->post(route('google.redirect'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('google');

        Http::assertNothingSent();
    }

    public function test_a_token_that_cloudflare_rejects_is_turned_away(): void
    {
        Http::fake([
            self::VERIFY_URL => Http::response(['success' => false, 'error-codes' => ['invalid-input-response']]),
        ]);

        $this->from(route('login'))
            ->post(route('google.redirect'), ['cf-turnstile-response' => 'token-palsu'])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('google');
    }

    public function test_a_token_that_cloudflare_accepts_opens_the_door(): void
    {
        Http::fake([
            self::VERIFY_URL => Http::response(['success' => true]),
        ]);

        $response = $this->post(route('google.redirect'), ['cf-turnstile-response' => 'token-asli']);

        $this->assertStringContainsString(
            'accounts.google.com',
            $response->headers->get('Location') ?? '',
        );

        Http::assertSent(function (Request $request): bool {
            return $request->url() === self::VERIFY_URL
                && $request['secret'] === 'secret-uji'
                && $request['response'] === 'token-asli';
        });
    }

    public function test_the_door_stays_open_when_cloudflare_cannot_be_reached(): void
    {
        Http::fake(fn () => throw new ConnectionException('timeout'));

        $response = $this->post(route('google.redirect'), ['cf-turnstile-response' => 'token-asli']);

        $this->assertStringContainsString(
            'accounts.google.com',
            $response->headers->get('Location') ?? '',
        );
    }

    public function test_nothing_is_verified_while_the_switch_is_off(): void
    {
        config(['services.turnstile.enabled' => false]);

        Http::fake();

        $this->post(route('google.redirect'));

        Http::assertNothingSent();
    }

    public function test_the_sign_in_screen_only_carries_the_site_key_when_it_is_on(): void
    {
        config(['services.turnstile.site_key' => 'site-key-uji']);

        $this->get(route('login'))
            ->assertInertia(fn ($page) => $page->where('turnstileSiteKey', 'site-key-uji'));

        config(['services.turnstile.enabled' => false]);

        $this->get(route('login'))
            ->assertInertia(fn ($page) => $page->where('turnstileSiteKey', null));
    }
}
