<?php

namespace App\Exceptions;

use RuntimeException;

class GoogleCalendarUnavailable extends RuntimeException
{
    public static function scopeMissing(string $email): self
    {
        return new self(
            "Akun {$email} belum memberi izin kalender. Minta yang bersangkutan keluar lalu masuk lagi lewat Google."
        );
    }

    public static function noUsableToken(string $email): self
    {
        return new self(
            "Token Google untuk {$email} tidak bisa diperbarui. Minta yang bersangkutan masuk lagi lewat Google."
        );
    }

    public static function tokenRejected(string $email): self
    {
        return new self(
            "Google menolak token milik {$email}; aksesnya kemungkinan sudah dicabut. Perlu masuk ulang lewat Google."
        );
    }
}
