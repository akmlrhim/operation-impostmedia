<?php

namespace App\Models;

use App\Support\CompanyProfile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * @property string|null $signature_path
 */
class CompanySetting extends Model
{
    protected $guarded = ['id'];

    public const DISK = 'local';

    public static function current(): self
    {
        return static::query()->firstOrCreate([]);
    }

    public function signatureUrl(): ?string
    {
        return $this->fileUrl('signature', $this->signature_path);
    }

    public function stampUrl(): ?string
    {
        return $this->fileUrl('stamp', $this->stamp_path);
    }

    public function imagePath(string $kind): ?string
    {
        return match ($kind) {
            'signature' => $this->signature_path,
            'stamp' => $this->stamp_path,
            default => null,
        };
    }

    public function signatureData(): ?string
    {
        return $this->fileData($this->signature_path);
    }

    public function stampData(): ?string
    {
        return $this->fileData($this->stamp_path);
    }

    /**
     * @return array<string, string|null>
     */
    public function documentImages(): array
    {
        return [
            'logo' => CompanyProfile::logoData(),
            'signature' => $this->signatureData(),
            'stamp' => $this->stampData(),
        ];
    }

    public function replaceImage(string $column, ?UploadedFile $file, bool $remove): ?string
    {
        $current = $this->{$column};

        if ($file !== null) {
            $stored = $file->store('company', self::DISK);

            if ($stored === false) {
                return $current;
            }

            $this->deleteFile($current);

            return $stored;
        }

        if ($remove) {
            $this->deleteFile($current);

            return null;
        }

        return $current;
    }

    private function deleteFile(?string $path): void
    {
        if ($path !== null && Storage::disk(self::DISK)->exists($path)) {
            Storage::disk(self::DISK)->delete($path);
        }
    }

    private function fileUrl(string $kind, ?string $path): ?string
    {
        if ($path === null) {
            return null;
        }

        return route('company.image', ['kind' => $kind]).'?v='.substr(md5($path), 0, 8);
    }

    private function fileData(?string $path): ?string
    {
        if ($path === null || ! Storage::disk(self::DISK)->exists($path)) {
            return null;
        }

        $disk = Storage::disk(self::DISK);
        $mime = $disk->mimeType($path) ?: 'image/png';

        return 'data:'.$mime.';base64,'.base64_encode((string) $disk->get($path));
    }
}
