<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * @property string $name
 * @property string|null $logo_path
 * @property string|null $signature_path
 */
class CompanySetting extends Model
{
    protected $guarded = ['id'];

    public const DISK = 'public';

    public static function current(): self
    {
        return static::query()->firstOrCreate([], ['name' => config('app.name')]);
    }

    public function logoUrl(): ?string
    {
        return $this->fileUrl($this->logo_path);
    }

    public function signatureUrl(): ?string
    {
        return $this->fileUrl($this->signature_path);
    }

    public function stampUrl(): ?string
    {
        return $this->fileUrl($this->stamp_path);
    }

    public function logoData(): ?string
    {
        return $this->fileData($this->logo_path);
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
            'logo' => $this->logoData(),
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

    private function fileUrl(?string $path): ?string
    {
        return $path === null ? null : Storage::disk(self::DISK)->url($path);
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
