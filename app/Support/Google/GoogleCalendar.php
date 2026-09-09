<?php

namespace App\Support\Google;

use App\Exceptions\GoogleCalendarUnavailable;
use App\Models\GoogleAccount;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class GoogleCalendar
{
    public const SCOPE = 'https://www.googleapis.com/auth/calendar.events';

    private const BASE_URL = 'https://www.googleapis.com/calendar/v3';

    private const CALENDAR_ID = 'primary';

    public function __construct(private readonly GoogleAccount $account) {}

    public static function for(GoogleAccount $account): self
    {
        return new self($account);
    }

    /**
     * @param  array<string, mixed>  $event  Payload event Google Calendar
     * @return array<string, mixed> Event yang tersimpan, termasuk id dan htmlLink
     */
    public function createEvent(array $event): array
    {
        return $this->send('post', '/events', $event);
    }

    public function deleteEvent(string $eventId): void
    {
        $response = $this->request()->delete($this->url("/events/{$eventId}"));

        if ($response->status() === 404 || $response->status() === 410) {
            return;
        }

        $this->guard($response)->throw();
    }

    /**
     * @param  list<string>  $attendees
     * @return array<string, mixed>
     */
    public static function event(
        string $title,
        \DateTimeInterface $start,
        \DateTimeInterface $end,
        ?string $description = null,
        array $attendees = [],
    ): array {
        $timezone = config('app.timezone') ?: 'UTC';

        return array_filter([
            'summary' => $title,
            'description' => $description,
            'start' => ['dateTime' => $start->format(\DateTimeInterface::RFC3339), 'timeZone' => $timezone],
            'end' => ['dateTime' => $end->format(\DateTimeInterface::RFC3339), 'timeZone' => $timezone],
            'attendees' => array_map(fn (string $email): array => ['email' => $email], $attendees),
        ], fn (mixed $value): bool => $value !== null && $value !== []);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function send(string $method, string $path, array $payload): array
    {
        $response = $this->request()->{$method}($this->url($path), $payload);

        /** @var array<string, mixed> */
        return $this->guard($response)->throw()->json();
    }

    private function request(): PendingRequest
    {
        if (! $this->account->hasScope(self::SCOPE)) {
            throw GoogleCalendarUnavailable::scopeMissing($this->account->email);
        }

        $token = $this->account->usableAccessToken();

        if ($token === null) {
            throw GoogleCalendarUnavailable::noUsableToken($this->account->email);
        }

        return Http::withToken($token)->acceptJson();
    }

    private function guard(Response $response): Response
    {
        if ($response->status() === 401 || $response->status() === 403) {
            throw GoogleCalendarUnavailable::tokenRejected($this->account->email);
        }

        return $response;
    }

    private function url(string $path): string
    {
        return self::BASE_URL.'/calendars/'.self::CALENDAR_ID.$path;
    }
}
