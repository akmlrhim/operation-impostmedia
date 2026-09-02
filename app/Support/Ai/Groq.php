<?php

namespace App\Support\Ai;

use App\Exceptions\AiUnavailable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class Groq
{
    private const URL = 'https://api.groq.com/openai/v1/chat/completions';

    private const TIMEOUT_SECONDS = 20;

    public static function configured(): bool
    {
        return (bool) config('services.groq.key');
    }

    /**
     * @return array<string, mixed>
     *
     * @throws AiUnavailable
     */
    public function json(string $system, string $prompt, float $temperature = 0.4): array
    {
        if (! self::configured()) {
            throw AiUnavailable::notConfigured();
        }

        try {
            $response = Http::withToken((string) config('services.groq.key'))
                ->timeout(self::TIMEOUT_SECONDS)
                ->post(self::URL, [
                    'model' => (string) config('services.groq.model'),
                    'temperature' => $temperature,
                    'response_format' => ['type' => 'json_object'],
                    'messages' => [
                        ['role' => 'system', 'content' => $system],
                        ['role' => 'user', 'content' => $prompt],
                    ],
                ]);
        } catch (ConnectionException $e) {
            report($e);

            throw AiUnavailable::unreachable();
        }

        if (! $response->successful()) {
            throw AiUnavailable::rejected($response->status());
        }

        $content = $response->json('choices.0.message.content');

        if (! is_string($content) || trim($content) === '') {
            throw AiUnavailable::emptyAnswer();
        }

        $decoded = json_decode($content, associative: true);

        if (! is_array($decoded)) {
            throw AiUnavailable::emptyAnswer();
        }

        return $decoded;
    }
}
