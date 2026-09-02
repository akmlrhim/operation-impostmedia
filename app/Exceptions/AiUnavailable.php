<?php

namespace App\Exceptions;

use RuntimeException;

class AiUnavailable extends RuntimeException
{
    public static function notConfigured(): self
    {
        return new self('Penulis otomatis belum aktif: GROQ_API_KEY belum diisi di server.');
    }

    public static function unreachable(): self
    {
        return new self('Layanan AI tidak bisa dihubungi. Coba lagi sebentar, atau ketik sendiri.');
    }

    public static function rejected(int $status): self
    {
        return new self("Layanan AI menolak permintaan (kode {$status}). Periksa GROQ_API_KEY dan GROQ_MODEL di server, atau ketik sendiri.");
    }

    public static function emptyAnswer(): self
    {
        return new self('Layanan AI tidak mengembalikan tulisan apa pun. Coba lagi, atau ketik sendiri.');
    }
}
