<?php

namespace App\Models;

use App\Support\Google\GoogleOAuth;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string $google_id
 * @property string $email
 * @property string|null $avatar_url
 * @property string $access_token
 * @property string|null $refresh_token
 * @property Carbon|null $token_expires_at
 * @property list<string>|null $scopes
 * @property Carbon|null $synced_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Hidden(['access_token', 'refresh_token'])]
class GoogleAccount extends Model
{
    private const EXPIRY_LEEWAY_SECONDS = 60;

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'access_token' => 'encrypted',
            'refresh_token' => 'encrypted',
            'token_expires_at' => 'datetime',
            'scopes' => 'array',
            'synced_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tokenExpired(): bool
    {
        return $this->token_expires_at !== null
            && $this->token_expires_at->subSeconds(self::EXPIRY_LEEWAY_SECONDS)->isPast();
    }

    public function hasScope(string $scope): bool
    {
        return in_array($scope, $this->scopes ?? [], strict: true);
    }

    public function usableAccessToken(): ?string
    {
        if (! $this->tokenExpired()) {
            return $this->access_token;
        }

        if ($this->refresh_token === null) {
            return null;
        }

        $token = GoogleOAuth::provider()->refreshToken($this->refresh_token);

        $this->forceFill([
            'access_token' => $token->token,
            'refresh_token' => $token->refreshToken ?: $this->refresh_token,
            'token_expires_at' => now()->addSeconds($token->expiresIn),
            'synced_at' => now(),
        ])->save();

        return $this->access_token;
    }
}
