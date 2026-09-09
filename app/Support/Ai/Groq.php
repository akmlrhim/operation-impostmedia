<?php

namespace App\Support\Ai;

use App\Exceptions\AiUnavailable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Groq
{
    private const URL = 'https://api.groq.com/openai/v1/chat/completions';

    private const TIMEOUT_SECONDS = 20;

    private const EFFORTS = ['low', 'medium', 'high'];

    public static function configured(): bool
    {
        return (bool) config('services.groq.key');
    }

    public static function lightModel(): string
    {
        return (string) config('services.groq.light_model');
    }

    /**
     * @param  int|null  $maxTokens  Batas token jawaban. Jangan terlalu ketat:
     *                               token berpikir ikut dihitung di sini, dan
     *                               jawaban yang terpotong bikin JSON-nya gagal.
     * @return array<string, mixed>
     *
     * @throws AiUnavailable
     */
    public function json(
        string $system,
        string $prompt,
        float $temperature = 0.4,
        ?int $maxTokens = null,
        ?string $model = null,
    ): array {
        if (! self::configured()) {
            throw AiUnavailable::notConfigured();
        }

        $model ??= (string) config('services.groq.model');

        try {
            $response = Http::withToken((string) config('services.groq.key'))
                ->timeout(self::TIMEOUT_SECONDS)
                ->post(self::URL, $this->payload($system, $prompt, $temperature, $maxTokens, $model));
        } catch (ConnectionException $e) {
            report($e);

            throw AiUnavailable::unreachable();
        }

        if ($response->status() === 429) {
            throw AiUnavailable::quotaExhausted();
        }

        if ($response->status() === 403) {
            throw AiUnavailable::modelNotPermitted($model);
        }

        if (! $response->successful()) {
            throw AiUnavailable::rejected($response->status());
        }

        $this->recordUsage($model, $response->json('usage'));

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

    /**
     * @return array<string, mixed>
     */
    private function payload(
        string $system,
        string $prompt,
        float $temperature,
        ?int $maxTokens,
        string $model,
    ): array {
        $payload = [
            'model' => $model,
            'temperature' => $temperature,
            'response_format' => ['type' => 'json_object'],
            'max_completion_tokens' => $maxTokens ?? (int) config('services.groq.max_tokens'),
            'messages' => [
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => $prompt],
            ],
        ];

        $effort = (string) config('services.groq.reasoning_effort');

        if (in_array($effort, self::EFFORTS, strict: true) && $this->thinks($model)) {
            $payload['reasoning_effort'] = $effort;
        }

        return $payload;
    }

    /**
     * groq/compound menolak reasoning_effort dengan 400, jadi parameter itu
     * hanya ikut untuk model yang memang berpikir dulu sebelum menjawab.
     */
    private function thinks(string $model): bool
    {
        foreach ((array) config('services.groq.reasoning_models') as $fragment) {
            if (is_string($fragment) && $fragment !== '' && str_contains($model, trim($fragment))) {
                return true;
            }
        }

        return false;
    }

    private function recordUsage(string $model, mixed $usage): void
    {
        if (! is_array($usage)) {
            return;
        }

        Log::debug('Pemakaian token Groq', [
            'model' => $model,
            'prompt' => $usage['prompt_tokens'] ?? null,
            'completion' => $usage['completion_tokens'] ?? null,
            'total' => $usage['total_tokens'] ?? null,
        ]);
    }
}
