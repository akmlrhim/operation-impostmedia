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

    public static function quotaExhausted(): self
    {
        return new self('Jatah penulis otomatis untuk hari ini sudah habis. Coba lagi besok, atau ketik sendiri.');
    }

    /**
     * Model yang dipilih ada, tapi belum diizinkan untuk kunci ini. Nama model
     * disebut karena yang membaca pesan ini pengelola server, bukan orang kantor:
     * kesalahannya cuma bisa dibereskan dari konsol Groq.
     */
    public static function modelNotPermitted(string $model): self
    {
        return new self("Model \"{$model}\" belum diizinkan untuk kunci Groq ini. Izinkan model itu beserta model dasarnya di konsol Groq, atau pilih model lain.");
    }

    public static function emptyAnswer(): self
    {
        return new self('Layanan AI tidak mengembalikan tulisan apa pun. Coba lagi, atau ketik sendiri.');
    }
}
