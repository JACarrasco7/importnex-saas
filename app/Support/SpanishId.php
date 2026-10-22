<?php

namespace App\Support;

/** Validación y normalización de DNI/NIE españoles (letra de control incluida). */
class SpanishId
{
    public static function normalize(?string $value): string
    {
        return strtoupper(preg_replace('/[\s.-]+/', '', (string) $value) ?? '');
    }

    /** DNI (8 cifras + letra) o NIE (X/Y/Z + 7 cifras + letra) con letra correcta. */
    public static function isValid(?string $value): bool
    {
        if (! preg_match('/^([XYZ]\d{7}|\d{8})([A-Z])$/', self::normalize($value), $m)) {
            return false;
        }
        $number = (int) strtr($m[1], ['X' => '0', 'Y' => '1', 'Z' => '2']);

        return 'TRWAGMYFPDXBNJZSQVHLCKE'[$number % 23] === $m[2];
    }
}
