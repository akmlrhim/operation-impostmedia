<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Models\Concerns\BroadcastsCrmChanges;
use App\Models\Concerns\HasAttachments;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property string $amount
 * @property Carbon $paid_at
 * @property PaymentMethod $method
 * @property string|null $proof_path
 */
class Payment extends Model
{
    use BroadcastsCrmChanges, HasAttachments;

    protected $guarded = ['id'];

    public const PROOF_DISK = 'local';

    /**
     * @var list<string>
     */
    protected $appends = ['proof_url'];

    public function crmBroadcastTopic(): string
    {
        return 'invoices';
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'method' => PaymentMethod::class,
            'amount' => 'decimal:2',
            'paid_at' => 'date',
        ];
    }

    /** @return BelongsTo<Invoice, $this> */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /** @return BelongsTo<User, $this> */
    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function getProofUrlAttribute(): ?string
    {
        if ($this->proof_path === null) {
            return null;
        }

        return route('payments.proof', $this).'?v='.substr(md5($this->proof_path), 0, 8);
    }

    public function replaceProof(UploadedFile $file): ?string
    {
        $stored = $file->store('payments/proofs', self::PROOF_DISK);

        if ($stored === false) {
            return $this->proof_path;
        }

        $this->deleteProofFile();

        return $stored;
    }

    public function deleteProofFile(): void
    {
        if ($this->proof_path !== null && Storage::disk(self::PROOF_DISK)->exists($this->proof_path)) {
            Storage::disk(self::PROOF_DISK)->delete($this->proof_path);
        }
    }

    protected static function booted(): void
    {
        static::deleting(function (Payment $payment): void {
            $payment->deleteProofFile();
        });
    }
}
