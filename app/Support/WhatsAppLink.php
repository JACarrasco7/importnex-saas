<?php

namespace App\Support;

/**
 * Enlaces `wa.me` con mensaje prellenado. Abren el WhatsApp del gestor con el
 * texto listo: no requiere API de WhatsApp Business ni proveedor de pago.
 */
class WhatsAppLink
{
    /** Normaliza a formato internacional sin símbolos (España por defecto). */
    public static function normalizePhone(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);
        if (! $digits) {
            return null;
        }
        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }
        if (strlen($digits) === 9) {
            $digits = '34'.$digits;
        }

        return strlen($digits) >= 10 ? $digits : null;
    }

    /** Enlace a un número concreto. Sin número válido devuelve el selector de chat. */
    public static function to(?string $phone, string $text): string
    {
        $number = self::normalizePhone($phone);
        $query = 'text='.rawurlencode($text);

        return $number ? "https://wa.me/{$number}?{$query}" : "https://wa.me/?{$query}";
    }
}
