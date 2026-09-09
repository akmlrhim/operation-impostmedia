<?php

namespace App\Models;

use App\Enums\BillingCycle;
use App\Enums\ContractStatus;
use App\Enums\ContractType;
use App\Models\Concerns\BroadcastsCrmChanges;
use App\Models\Concerns\HasActivities;
use App\Models\Concerns\HasAttachments;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property int|null $client_id
 * @property-read Client|null $client
 * @property string $number
 * @property string $title
 * @property ContractType $type
 * @property ContractStatus $status
 * @property BillingCycle $billing_cycle
 * @property string $subtotal
 * @property string $discount_amount
 * @property string $tax_percent
 * @property string $tax_amount
 * @property string $value
 * @property array<string, mixed>|null $ai_clauses
 * @property bool|null $ai_requires_visit
 * @property Carbon|null $start_date
 * @property Carbon|null $end_date
 * @property Carbon|null $signed_date
 * @property Carbon|null $next_invoice_date
 * @property string|null $signature_path
 */
class Contract extends Model
{
    use BroadcastsCrmChanges, HasActivities, HasAttachments, SoftDeletes;

    protected $guarded = ['id'];

    public const SIGNATURE_DISK = 'local';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => ContractType::class,
            'status' => ContractStatus::class,
            'billing_cycle' => BillingCycle::class,
            'ai_clauses' => 'array',
            'ai_requires_visit' => 'boolean',
            'start_date' => 'date',
            'end_date' => 'date',
            'signed_date' => 'date',
            'next_invoice_date' => 'date',
            'subtotal' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_percent' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'value' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<Client, $this> */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /** @return BelongsTo<Lead, $this> */
    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    /** @return HasMany<ContractItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(ContractItem::class)->orderBy('position');
    }

    /**
     * HTML MoU terukur ratusan kilobyte per dokumen, jadi ia duduk di tabel
     * terpisah supaya route-model binding dan halaman daftar tidak ikut
     * menariknya dari MySQL.
     *
     * @return HasOne<ContractDocument, $this>
     */
    public function document(): HasOne
    {
        return $this->hasOne(ContractDocument::class);
    }

    public function renderedBody(): ?string
    {
        return $this->document()->value('body');
    }

    public function editedBody(): ?string
    {
        return $this->document()->value('document_body');
    }

    public function isDocumentEdited(): bool
    {
        return $this->document()->whereNotNull('document_body')->exists();
    }

    public function cacheRenderedBody(string $html): void
    {
        $this->document()->updateOrCreate([], ['body' => $html]);
    }

    /**
     * Suntingan tangan melepas MoU dari template, jadi hasil render yang lama
     * ikut dibuang supaya tidak ada dua versi yang saling menimpa.
     */
    public function saveEditedBody(string $html): void
    {
        $this->document()->updateOrCreate([], ['document_body' => $html, 'body' => null]);
    }

    public function forgetDocument(): void
    {
        $this->document()->delete();
    }

    public function signatureUrl(): ?string
    {
        if ($this->signature_path === null) {
            return null;
        }

        return route('contracts.signature', $this).'?v='.substr(md5($this->signature_path), 0, 8);
    }

    public function signatureData(): ?string
    {
        $disk = Storage::disk(self::SIGNATURE_DISK);

        if ($this->signature_path === null || ! $disk->exists($this->signature_path)) {
            return null;
        }

        $mime = $disk->mimeType($this->signature_path) ?: 'image/png';

        return 'data:'.$mime.';base64,'.base64_encode((string) $disk->get($this->signature_path));
    }

    public function replaceSignature(UploadedFile $file): ?string
    {
        $stored = $file->store('contracts/signatures', self::SIGNATURE_DISK);

        if ($stored === false) {
            return $this->signature_path;
        }

        $this->deleteSignatureFile();

        return $stored;
    }

    public function deleteSignatureFile(): void
    {
        if ($this->signature_path !== null && Storage::disk(self::SIGNATURE_DISK)->exists($this->signature_path)) {
            Storage::disk(self::SIGNATURE_DISK)->delete($this->signature_path);
        }
    }

    protected static function booted(): void
    {
        static::deleting(function (Contract $contract): void {
            Invoice::where('contract_id', $contract->getKey())->update(['contract_id' => null]);
        });
    }

    /** @return HasMany<Invoice, $this> */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function canIssueInvoice(): bool
    {
        return $this->invoiceBlocker() === null;
    }

    public function invoiceBlocker(): ?string
    {
        return match (true) {
            $this->client_id === null => 'MoU ini sudah tidak punya klien, jadi invoicenya tidak bisa diterbitkan.',
            ! $this->status->canBeInvoiced() => 'MoU harus ditandatangani dulu sebelum bisa diterbitkan invoice.',
            $this->billing_cycle === BillingCycle::OneTime && $this->invoices()->exists() => 'MoU sekali bayar hanya boleh punya satu invoice. Hapus invoice lamanya kalau mau menerbitkan ulang.',
            default => null,
        };
    }

    /**
     * @param  Builder<Contract>  $query
     */
    public function scopeExpiringWithin(Builder $query, int $days): void
    {
        $query->whereIn('status', [ContractStatus::Signed, ContractStatus::Active])
            ->whereNotNull('end_date')
            ->whereBetween('end_date', [now(), now()->addDays($days)]);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function aiClausePoints(): array
    {
        $clauses = [];

        foreach ((array) ($this->ai_clauses ?? []) as $id => $points) {
            $clean = array_values(array_filter(
                (array) $points,
                fn ($point): bool => is_string($point) && trim($point) !== '',
            ));

            if ($clean !== []) {
                $clauses[(string) $id] = $clean;
            }
        }

        return $clauses;
    }

    /**
     * Pakai hasil bacaan AI (dari WriteContractClauses, yang membaca deskripsi
     * layanan & catatan ruang lingkup) kalau sudah ada; kalau belum pernah
     * ditulis AI, jatuh ke flag manual per paket sebagai perkiraan awal.
     */
    public function requiresVisitClause(): bool
    {
        if ($this->ai_requires_visit !== null) {
            return $this->ai_requires_visit;
        }

        return $this->items->contains(
            fn (ContractItem $item): bool => (bool) $item->servicePackage?->requires_visit,
        );
    }

    public function recalculate(): void
    {
        $subtotal = (float) $this->items()->sum('amount');
        $base = max($subtotal - (float) $this->discount_amount, 0);
        $tax = $base * ((float) $this->tax_percent / 100);

        $this->forceFill([
            'subtotal' => $subtotal,
            'tax_amount' => $tax,
            'value' => $base + $tax,
        ])->save();
    }
}
