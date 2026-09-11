<?php

return [

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'brevo' => [
        'key' => env('BREVO_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT', env('APP_URL').'/auth/google/callback'),

        'scopes' => env('GOOGLE_CALENDAR_SCOPE', true)
            ? ['https://www.googleapis.com/auth/calendar.events']
            : [],
    ],

    'groq' => [
        'key' => env('GROQ_API_KEY'),
        'model' => env('GROQ_MODEL', 'openai/gpt-oss-120b'),

        /*
        | Model kedua untuk tugas ringan (rincian per baris pekerjaan). Kuota
        | Groq dihitung per model, jadi memisahkannya memberi jatah sendiri.
        */
        'light_model' => env('GROQ_LIGHT_MODEL', env('GROQ_MODEL', 'openai/gpt-oss-120b')),

        /*
        | Model gpt-oss berpikir dulu sebelum menjawab, dan token berpikir itu
        | ikut ditagih. Hanya 'low', 'medium', atau 'high' yang diterima Groq.
        */
        'reasoning_effort' => env('GROQ_REASONING_EFFORT', 'low'),

        /*
        | Tidak semua model menerima reasoning_effort; groq/compound menolaknya
        | dengan 400. Potongan nama di sini yang menentukan kapan parameter itu
        | ikut dikirim, jadi ganti model tidak perlu ganti kode.
        */
        'reasoning_models' => array_filter(
            explode(',', (string) env('GROQ_REASONING_MODELS', 'gpt-oss')),
        ),

        'max_tokens' => (int) env('GROQ_MAX_TOKENS', 1500),

        'cache_days' => (int) env('GROQ_CACHE_DAYS', 30),
    ],

    'turnstile' => [
        'site_key' => env('TURNSTILE_SITE_KEY'),
        'secret' => env('TURNSTILE_SECRET_KEY'),

        'enabled' => env('TURNSTILE_ENABLED', false),
    ],

];
