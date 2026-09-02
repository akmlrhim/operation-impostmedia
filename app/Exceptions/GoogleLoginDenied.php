<?php

namespace App\Exceptions;

use RuntimeException;

class GoogleLoginDenied extends RuntimeException
{
    public static function inactive(): self
    {
        return new self('Akses akun ini sedang dinonaktifkan. Hubungi admin.');
    }

    public static function linkedToAnotherUser(): self
    {
        return new self('Akun Google ini sudah tertaut ke user lain.');
    }

    public static function emailTaken(): self
    {
        return new self('Alamat email akun Google ini sudah dipakai user lain.');
    }

    public static function noEmail(): self
    {
        return new self('Akun Google ini tidak memberikan alamat email.');
    }
}
