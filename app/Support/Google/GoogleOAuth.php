<?php

namespace App\Support\Google;

use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\GoogleProvider;
use Laravel\Socialite\Two\User as GoogleUser;
use RuntimeException;

class GoogleOAuth
{
    public static function provider(): GoogleProvider
    {
        $provider = Socialite::driver('google');

        if (! $provider instanceof GoogleProvider) {
            throw new RuntimeException('Driver Google Socialite bukan provider OAuth 2.');
        }

        return $provider;
    }

    public static function user(): GoogleUser
    {
        $user = self::provider()->user();

        if (! $user instanceof GoogleUser) {
            throw new RuntimeException('Balikan Google tidak memuat token OAuth 2.');
        }

        return $user;
    }
}
