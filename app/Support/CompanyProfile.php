<?php

namespace App\Support;

class CompanyProfile
{
    /**
     * @var array<int, string>
     */
    public const IDENTITY = [
        'name',
        'address',
        'city',
        'phone',
        'email',
        'signatory_name',
        'signatory_position',
    ];

    /**
     * @var array<int, string>
     */
    public const INVOICE_TEXT = [
        'invoice_notes',
        'terms',
    ];

    private static ?string $logo = null;

    /**
     * @return array<string, string>
     */
    public static function identity(): array
    {
        return self::values(self::IDENTITY);
    }

    /**
     * @return array<string, string>
     */
    public static function all(): array
    {
        return self::values([...self::IDENTITY, ...self::INVOICE_TEXT]);
    }

    /**
     * @param  array<int, string>  $keys
     * @return array<string, string>
     */
    private static function values(array $keys): array
    {
        $values = [];

        foreach ($keys as $key) {
            $values[$key] = self::value($key);
        }

        return $values;
    }

    public static function name(): string
    {
        return self::value('name');
    }

    public static function address(): string
    {
        return self::value('address');
    }

    public static function city(): string
    {
        return self::value('city');
    }

    public static function phone(): string
    {
        return self::value('phone');
    }

    public static function email(): string
    {
        return self::value('email');
    }

    public static function signatoryName(): string
    {
        return self::value('signatory_name');
    }

    public static function signatoryPosition(): string
    {
        return self::value('signatory_position');
    }

    public static function invoiceNotes(): string
    {
        return self::value('invoice_notes');
    }

    public static function terms(): string
    {
        return self::value('terms');
    }

    public static function logoUrl(): ?string
    {
        $path = self::logoPath();

        if ($path === null) {
            return null;
        }

        return '/'.self::value('logo').'?v='.(filemtime($path) ?: 0);
    }

    public static function logoData(): ?string
    {
        if (self::$logo !== null) {
            return self::$logo;
        }

        $path = self::logoPath();

        if ($path === null) {
            return null;
        }

        $contents = file_get_contents($path);

        if ($contents === false) {
            return null;
        }

        return self::$logo = 'data:image/png;base64,'.base64_encode($contents);
    }

    private static function logoPath(): ?string
    {
        $path = public_path(self::value('logo'));

        return is_file($path) ? $path : null;
    }

    private static function value(string $key): string
    {
        $value = config('company.'.$key);

        return is_string($value) ? $value : '';
    }
}
