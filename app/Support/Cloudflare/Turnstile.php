<?php

namespace App\Support\Cloudflare;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class Turnstile
{
    private const VERIFY_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    private const TIMEOUT_SECONDS = 5;

    public static function enabled(): bool
    {
        return (bool) config('services.turnstile.enabled');
    }

    /**
     * @param  string|null  $ip  IP pengunjung; dikirim sebagai bahan penilaian
     *                           tambahan, dan boleh kosong.
     */
    public function verify(?string $token, ?string $ip = null): bool
    {
        if ($token === null || $token === '') {
            return false;
        }

        try {
            $response = Http::asForm()
                ->timeout(self::TIMEOUT_SECONDS)
                ->post(self::VERIFY_URL, array_filter([
                    'secret' => config('services.turnstile.secret'),
                    'response' => $token,
                    'remoteip' => $ip,
                ]));
        } catch (ConnectionException $e) {
            report($e);

            return true;
        }

        return $response->successful() && $response->json('success') === true;
    }
}
