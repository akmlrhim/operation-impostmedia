<?php

namespace App\Models;

use App\Enums\ClientStatus;
use App\Models\Concerns\BroadcastsCrmChanges;
use App\Models\Concerns\HasActivities;
use App\Models\Concerns\HasAttachments;
use App\Models\Concerns\HasOwners;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string|null $short_code
 * @property string $company_name
 * @property ClientStatus $status
 * @property int|null $created_by
 * @property-read Collection<int, User> $assignees
 */
class Client extends Model
{
    use BroadcastsCrmChanges, HasActivities, HasAttachments, HasOwners, SoftDeletes;

    protected $guarded = ['id'];

    /**
     * @return array{subject: string, label: string, url: string}
     */
    public function notificationMeta(): array
    {
        return [
            'subject' => 'Klien',
            'label' => $this->company_name,
            'url' => route('clients.show', $this),
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ClientStatus::class,
        ];
    }

    /**
     * Klien yang dihapus meninggalkan MoU dan invoicenya berdiri sendiri, jadi
     * dokumennya tetap bisa dibuka dan diunduh tanpa induk.
     */
    protected static function booted(): void
    {
        static::deleting(function (Client $client): void {
            Contract::withTrashed()->where('client_id', $client->id)->update(['client_id' => null]);
            Invoice::withTrashed()->where('client_id', $client->id)->update(['client_id' => null]);
            Lead::withTrashed()->where('converted_client_id', $client->id)->update(['converted_client_id' => null]);
        });
    }

    /** @return HasMany<Lead, $this> */
    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class, 'converted_client_id');
    }

    /** @return HasMany<Contract, $this> */
    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }

    /** @return HasMany<Invoice, $this> */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public static function deriveShortCode(string $companyName): string
    {
        $legalForms = ['pt', 'cv', 'ud', 'pd', 'tbk', 'persero', 'perum', 'yayasan', 'koperasi'];

        /** @var array<int, string> $parts */
        $parts = preg_split('/[^\p{L}\p{N}]+/u', $companyName, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        $words = array_values(array_filter(
            $parts,
            fn (string $word): bool => ! in_array(Str::lower($word), $legalForms, true),
        ));

        if ($words === []) {
            return 'KLIEN';
        }

        $code = count($words) > 1
            ? implode('', array_map(fn (string $word): string => Str::substr($word, 0, 1), array_slice($words, 0, 5)))
            : Str::substr($words[0], 0, 3);

        return Str::upper($code);
    }

    public static function generateShortCode(string $companyName, ?int $ignoreId = null): string
    {
        $base = static::deriveShortCode($companyName);
        $code = $base;
        $suffix = 2;

        while (static::shortCodeTaken($code, $ignoreId)) {
            $code = $base.$suffix;
            $suffix++;
        }

        return $code;
    }

    protected static function shortCodeTaken(string $code, ?int $ignoreId): bool
    {
        return static::withTrashed()
            ->where('short_code', $code)
            ->when($ignoreId !== null, fn (Builder $query) => $query->whereKeyNot($ignoreId))
            ->exists();
    }

    /**
     * @return array<string, mixed>
     */
    public function billingSnapshot(): array
    {
        return [
            'company_name' => $this->company_name,
            'address' => $this->address,
            'city' => $this->city,
            'email' => $this->email,
            'phone' => $this->phone,
            'contact_name' => $this->contact_name,
        ];
    }
}
