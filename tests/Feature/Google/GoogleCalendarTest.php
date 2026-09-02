<?php

namespace Tests\Feature\Google;

use App\Exceptions\GoogleCalendarUnavailable;
use App\Models\GoogleAccount;
use App\Models\User;
use App\Support\Google\GoogleCalendar;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GoogleCalendarTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_an_event_sends_the_stored_token_to_google(): void
    {
        Http::fake([
            'www.googleapis.com/calendar/v3/*' => Http::response([
                'id' => 'event-123',
                'htmlLink' => 'https://calendar.google.com/event?eid=abc',
            ]),
        ]);

        $account = $this->accountWithCalendar();

        $event = GoogleCalendar::for($account)->createEvent(GoogleCalendar::event(
            title: 'Follow up Klien A',
            start: now()->addDay(),
            end: now()->addDay()->addMinutes(30),
        ));

        $this->assertSame('event-123', $event['id']);

        Http::assertSent(function (Request $request): bool {
            return $request->url() === 'https://www.googleapis.com/calendar/v3/calendars/primary/events'
                && $request->method() === 'POST'
                && $request->hasHeader('Authorization', 'Bearer access-token-lama')
                && $request['summary'] === 'Follow up Klien A';
        });
    }

    public function test_an_account_without_the_calendar_scope_never_reaches_google(): void
    {
        Http::fake();

        $account = $this->accountWithCalendar(scopes: ['openid']);

        $this->expectException(GoogleCalendarUnavailable::class);

        try {
            GoogleCalendar::for($account)->createEvent(['summary' => 'Tidak akan terkirim']);
        } finally {
            Http::assertNothingSent();
        }
    }

    public function test_a_revoked_token_is_reported_as_needing_a_new_sign_in(): void
    {
        Http::fake([
            'www.googleapis.com/calendar/v3/*' => Http::response(['error' => 'invalid'], 401),
        ]);

        $account = $this->accountWithCalendar();

        $this->expectException(GoogleCalendarUnavailable::class);
        $this->expectExceptionMessageMatches('/masuk ulang lewat Google/');

        GoogleCalendar::for($account)->createEvent(['summary' => 'Ditolak']);
    }

    public function test_deleting_an_event_that_is_already_gone_is_not_an_error(): void
    {
        Http::fake([
            'www.googleapis.com/calendar/v3/*' => Http::response(['error' => 'notFound'], 404),
        ]);

        $account = $this->accountWithCalendar();

        GoogleCalendar::for($account)->deleteEvent('sudah-dihapus-user');

        Http::assertSentCount(1);
    }

    public function test_the_event_payload_carries_the_application_timezone(): void
    {
        config(['app.timezone' => 'Asia/Jakarta']);

        $event = GoogleCalendar::event(
            title: 'Meeting',
            start: now(),
            end: now()->addHour(),
        );

        $this->assertSame('Asia/Jakarta', $event['start']['timeZone']);
        $this->assertSame('Asia/Jakarta', $event['end']['timeZone']);
        $this->assertArrayNotHasKey('description', $event);
    }

    /**
     * @param  list<string>|null  $scopes
     */
    private function accountWithCalendar(?array $scopes = null): GoogleAccount
    {
        $user = User::factory()->create();

        return GoogleAccount::query()->create([
            'user_id' => $user->id,
            'google_id' => 'google-sub-123',
            'email' => $user->email,
            'access_token' => 'access-token-lama',
            'refresh_token' => 'refresh-token-lama',
            'token_expires_at' => now()->addHour(),
            'scopes' => $scopes ?? [GoogleCalendar::SCOPE],
        ]);
    }
}
