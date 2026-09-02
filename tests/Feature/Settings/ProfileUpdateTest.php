<?php

namespace Tests\Feature\Settings;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed()
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('profile.edit'));

        $response->assertOk();
    }

    public function test_profile_information_can_be_updated()
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch(route('profile.update'), [
                'name' => 'Test User',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('profile.edit'));

        $this->assertSame('Test User', $user->refresh()->name);
    }

    public function test_the_email_address_cannot_be_changed_from_the_profile_page()
    {
        $user = User::factory()->create(['email' => 'dimas@operation.test']);

        $this->actingAs($user)
            ->patch(route('profile.update'), [
                'name' => 'Test User',
                'email' => 'lain@operation.test',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('dimas@operation.test', $user->refresh()->email);
    }

    public function test_email_verification_status_is_left_alone_on_update()
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patch(route('profile.update'), ['name' => 'Test User'])
            ->assertSessionHasNoErrors();

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_user_can_delete_their_account()
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete(route('profile.destroy'));

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('home'));

        $this->assertGuest();
        $this->assertNull($user->fresh());
    }

    public function test_deleting_an_account_takes_its_google_link_along()
    {
        $user = User::factory()->create();

        $user->googleAccount()->create([
            'google_id' => 'google-sub-123',
            'email' => $user->email,
            'access_token' => 'token',
            'scopes' => [],
        ]);

        $this->actingAs($user)->delete(route('profile.destroy'));

        $this->assertDatabaseEmpty('google_accounts');
    }
}
