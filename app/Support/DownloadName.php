<?php

namespace App\Support;

final class DownloadName
{
    public static function safe(string $name, string $fallback = 'dokumen'): string
    {
        $safe = trim((string) preg_replace('#[/\\\\]+#', '-', $name));

        return $safe === '' ? $fallback : $safe;
    }
}
