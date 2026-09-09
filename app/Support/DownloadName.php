<?php

namespace App\Support;

final class DownloadName
{
    private const MAX_LENGTH = 150;

    public static function safe(string $name, string $fallback = 'dokumen'): string
    {
        $safe = (string) preg_replace('#[/\\\\]+#', '-', $name);
        $safe = (string) preg_replace('/[\x00-\x1F\x7F"]+/u', '', $safe);
        $safe = trim(ltrim($safe, '.'));

        if (mb_strlen($safe) > self::MAX_LENGTH) {
            $safe = mb_substr($safe, 0, self::MAX_LENGTH);
        }

        return $safe === '' ? $fallback : $safe;
    }
}
