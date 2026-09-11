<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Models\GoogleAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\GoogleProvider;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

class GoogleLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_sign_in_screen_only_offers_google(): void
    {
        $this->get(route('login'))->assertOk();
    }

    public function test_the_button_sends_the_visitor_to_googles_consent_screen(): void
    {
        $response = $this->post(route('google.redirect'));

        $target = $response->headers->get('Location') ?? '';

        $this->assertStringContainsString('accounts.google.com', $target);
        $this->assertStringContainsString('access_type=offline', $target);
        $this->assertStringContainsString('prompt=consent', $target);
        $this->assertStringContainsString(
            urlencode('https://www.googleapis.com/auth/calendar.events'),
            $target,
        );
    }

    public function test_visitors_are_always_shown_the_account_chooser(): void
    {
        $user = User::factory()->create(['email' => 'dimas@operation.test']);
        $this->linkGoogleAccount($user, ['refresh_token' => 'refresh-token-lama']);

        $target = $this->withCookie('google_account_hint', 'dimas@operation.test')
            ->post(route('google.redirect'))
            ->headers->get('Location') ?? '';

        $this->assertStringContainsString('prompt=consent+select_account', $target);
        $this->assertStringNotContainsString('login_hint', $target);
    }

    /**
     * Tanpa refresh token tersimpan, Google harus dimintai persetujuan lagi,
     * kalau tidak token offline-nya tidak pernah datang dan Kalender mati.
     */
    public function test_consent_is_asked_again_when_no_refresh_token_is_held(): void
    {
        $user = User::factory()->create(['email' => 'dimas@operation.test']);
        $this->linkGoogleAccount($user, ['refresh_token' => null]);

        $target = $this->withCookie('google_account_hint', 'dimas@operation.test')
            ->post(route('google.redirect'))
            ->headers->get('Location') ?? '';

        $this->assertStringContainsString('prompt=consent', $target);
    }

    public function test_signing_in_remembers_the_account_for_the_next_visit(): void
    {
        User::factory()->create(['email' => 'dimas@operation.test']);

        $this->mockGoogleReturns($this->fakeGoogleUser());

        $this->get(route('google.callback'))
            ->assertRedirect(route('dashboard'))
            ->assertCookie('google_account_hint', 'dimas@operation.test');
    }

    public function test_a_registered_user_can_sign_in_and_their_token_is_stored(): void
    {
        $user = User::factory()->create(['email' => 'dimas@operation.test']);

        $this->mockGoogleReturns($this->fakeGoogleUser());

        $this->get(route('google.callback'))->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);

        $account = $user->fresh()->googleAccount;

        $this->assertNotNull($account);
        $this->assertSame('google-sub-123', $account->google_id);
        $this->assertSame('access-token-baru', $account->access_token);
        $this->assertSame('refresh-token-baru', $account->refresh_token);
        $this->assertTrue($account->hasScope('https://www.googleapis.com/auth/calendar.events'));
        $this->assertNotNull($account->token_expires_at);

        $this->assertNotSame('access-token-baru', DB::table('google_accounts')->value('access_token'));
    }

    public function test_the_very_first_person_to_sign_in_becomes_the_superuser(): void
    {
        $this->mockGoogleReturns($this->fakeGoogleUser(['email' => 'pendiri@gmail.com']));

        $this->get(route('google.callback'))->assertRedirect(route('dashboard'));

        $user = User::query()->where('email', 'pendiri@gmail.com')->firstOrFail();

        $this->assertSame(UserRole::Superuser, $user->role);
        $this->assertNotNull($user->approved_at);
        $this->assertTrue($user->is_active);
    }

    public function test_an_unknown_google_account_registers_itself_and_waits_for_an_admin(): void
    {
        User::factory()->create();

        $this->mockGoogleReturns($this->fakeGoogleUser(['email' => 'orangbaru@gmail.com']));

        $this->get(route('google.callback'))->assertRedirect(route('dashboard'));

        $user = User::query()->where('email', 'orangbaru@gmail.com')->first();

        $this->assertNotNull($user);
        $this->assertAuthenticatedAs($user);

        $this->assertSame(UserRole::Member, $user->role);
        $this->assertTrue($user->is_active);
        $this->assertNull($user->approved_at);
        $this->assertSame('Dimas Prasetyo', $user->name);

        $this->assertNotNull($user->googleAccount);
    }

    public function test_a_pending_registrant_is_held_at_the_waiting_room(): void
    {
        $user = User::factory()->pending()->create();

        $this->actingAs($user)->get(route('dashboard'))->assertRedirect(route('pending'));
        $this->actingAs($user)->get(route('clients.index'))->assertRedirect(route('pending'));

        $this->actingAs($user)->get(route('pending'))->assertOk();
    }

    public function test_an_approved_user_has_no_business_in_the_waiting_room(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('pending'))
            ->assertRedirect(route('dashboard'));
    }

    public function test_a_deactivated_account_is_turned_away(): void
    {
        User::factory()->inactive()->create(['email' => 'dimas@operation.test']);

        $this->mockGoogleReturns($this->fakeGoogleUser());

        $this->get(route('google.callback'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('google');

        $this->assertGuest();
    }

    public function test_an_email_changed_at_google_is_synced_through_the_google_id(): void
    {
        $user = User::factory()->create(['email' => 'lama@operation.test']);

        $this->linkGoogleAccount($user, ['email' => 'lama@operation.test']);

        $this->mockGoogleReturns($this->fakeGoogleUser(['email' => 'baru@operation.test']));

        $this->get(route('google.callback'))->assertRedirect(route('dashboard'));

        $this->assertSame('baru@operation.test', $user->fresh()->email);
        $this->assertSame('baru@operation.test', $user->fresh()->googleAccount->email);
    }

    public function test_the_stored_refresh_token_survives_a_login_without_a_new_one(): void
    {
        $user = User::factory()->create(['email' => 'dimas@operation.test']);

        $this->linkGoogleAccount($user, ['refresh_token' => 'refresh-token-lama']);

        $this->mockGoogleReturns($this->fakeGoogleUser(['refresh_token' => null]));

        $this->get(route('google.callback'))->assertRedirect(route('dashboard'));

        $this->assertSame('refresh-token-lama', $user->fresh()->googleAccount->refresh_token);
    }

    public function test_cancelling_at_googles_screen_returns_with_a_message(): void
    {
        $this->get(route('google.callback', ['error' => 'access_denied']))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('google');

        $this->assertGuest();
    }

    public function test_a_user_can_sign_out(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('logout'))
            ->assertRedirect('/');

        $this->assertGuest();
    }

    public function test_guests_are_sent_to_the_sign_in_screen(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_the_google_photo_is_shared_as_the_users_avatar(): void
    {
        $user = User::factory()->create(['email' => 'dimas@operation.test']);

        $this->mockGoogleReturns($this->fakeGoogleUser());

        $this->get(route('google.callback'));

        $this->get(route('dashboard'))->assertInertia(
            fn (AssertableInertia $page) => $page
                ->where('auth.user.avatar', 'https://lh3.googleusercontent.com/foto')
                ->missing('auth.user.googleAccount')
        );

        $this->assertSame('https://lh3.googleusercontent.com/foto', $user->fresh()->avatar);
    }

    public function test_a_user_without_a_google_account_has_no_avatar(): void
    {
        $this->assertNull(User::factory()->create()->avatar);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function fakeGoogleUser(array $attributes = []): SocialiteUser
    {
        $user = new SocialiteUser;

        $user->map([
            'id' => $attributes['id'] ?? 'google-sub-123',
            'name' => $attributes['name'] ?? 'Dimas Prasetyo',
            'email' => $attributes['email'] ?? 'dimas@operation.test',
            'avatar' => 'https://lh3.googleusercontent.com/foto',
        ]);

        $user->setRaw(['email_verified' => $attributes['email_verified'] ?? true]);

        $user->token = 'access-token-baru';
        $user->refreshToken = array_key_exists('refresh_token', $attributes)
            ? $attributes['refresh_token']
            : 'refresh-token-baru';
        $user->expiresIn = 3600;
        $user->approvedScopes = [
            'openid',
            'https://www.googleapis.com/auth/userinfo.email',
            'https://www.googleapis.com/auth/calendar.events',
        ];

        return $user;
    }

    private function mockGoogleReturns(SocialiteUser $googleUser): void
    {
        $provider = Mockery::mock(GoogleProvider::class);
        $provider->shouldReceive('user')->andReturn($googleUser);

        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function linkGoogleAccount(User $user, array $attributes = []): GoogleAccount
    {
        return GoogleAccount::query()->create([
            'user_id' => $user->id,
            'google_id' => 'google-sub-123',
            'email' => $attributes['email'] ?? $user->email,
            'access_token' => 'token-lama',
            'refresh_token' => $attributes['refresh_token'] ?? null,
            'scopes' => [],
        ]);
    }
}
