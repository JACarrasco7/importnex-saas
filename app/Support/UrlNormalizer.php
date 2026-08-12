<?php

namespace App\Support;

/**
 * Helper para normalizar URLs eliminando parámetros de tracking,
 * fragmentos y trailing slashes. Usado para comparar URLs del mismo
 * anuncio en diferentes contextos (chat JSON, BD, scraping).
 *
 * §2.1 — Extracción de lógica duplicada en ValuationImporter y extractores.
 */
class UrlNormalizer
{
    /**
     * Normaliza una URL eliminando query string, fragmentos y trailing slashes.
     *
     * Ejemplo:
     *   "https://www.mobile.de/fahrzeuge/details.html?id=123&utm_source=google#photos"
     *   → "https://www.mobile.de/fahrzeuge/details.html"
     *
     * @param  string|null  $url  URL a normalizar
     * @return string|null URL normalizada o null si la entrada es null/vacía
     */
    public static function normalize(?string $url): ?string
    {
        if (blank($url)) {
            return null;
        }

        $url = trim($url);
        $url = preg_replace('/[?#].*$/', '', $url);

        return rtrim($url, '/');
    }

    /**
     * Compara dos URLs ignorando parámetros de tracking.
     *
     * @return bool true si ambas URLs normalizadas son idénticas
     */
    public static function same(?string $url1, ?string $url2): bool
    {
        return self::normalize($url1) === self::normalize($url2);
    }
}
