<?php

namespace App\Support;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;

class EncodedId
{
    public static function encode(int|string|null $id): ?string
    {
        if ($id === null || $id === '') {
            return null;
        }

        $encrypted = Crypt::encryptString((string) $id);

        return rtrim(strtr($encrypted, '+/', '-_'), '=');
    }

    public static function decode(int|string|null $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_int($value) || ctype_digit((string) $value)) {
            return (int) $value;
        }

        $normalized = strtr((string) $value, '-_', '+/');
        $padding = strlen($normalized) % 4;
        if ($padding > 0) {
            $normalized .= str_repeat('=', 4 - $padding);
        }

        try {
            $decrypted = Crypt::decryptString($normalized);
        } catch (DecryptException) {
            return null;
        }

        return ctype_digit($decrypted) ? (int) $decrypted : null;
    }

    public static function decodeOrFail(int|string|null $value): int
    {
        $id = self::decode($value);

        if ($id === null) {
            abort(404, 'Không tìm thấy tài nguyên.');
        }

        return $id;
    }
}
