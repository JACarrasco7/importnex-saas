<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Geocoding ligero por nombre de ciudad (Nominatim / OpenStreetMap).
 *
 * Se usa al importar coches: si el informe no trae coordenadas pero sí ciudad,
 * se geolocaliza una vez por ciudad (cache permanente) para que el coche
 * aparezca en el mapa. Nominatim pide User-Agent identificable y máx. 1 req/s
 * — el cache permanente hace que en la práctica haya muy pocas peticiones.
 */
class GeoCoder
{
    private const CACHE_PREFIX = 'geocode.city.';

    /**
     * Devuelve ['lat' => float, 'lng' => float] o null si no se resuelve.
     *
     * @param  string  $city  Nombre de ciudad ("Laatzen (Niedersachsen)", "Múnich"...)
     * @return array{lat: float, lng: float}|null
     */
    public function geocodeCity(?string $city): ?array
    {
        if (! $city || trim($city) === '') {
            return null;
        }

        $key = self::CACHE_PREFIX.strtolower(preg_replace('/[^a-z0-9]+/i', '-', trim($city)));

        // Cache negativo: null no se cachea en Laravel, así que usamos el
        // centinela 'none' para no repetir peticiones de ciudades desconocidas.
        // OJO (auditoría 15-ago): solo se cachea 'none' cuando la API responde
        // 200 con 0 resultados. Errores transitorios (429/5xx/timeout) devuelven
        // null para que se reintente en la siguiente pasada.
        $cached = Cache::rememberForever($key, function () use ($city) {
            try {
                $response = Http::timeout(8)
                    ->withHeaders(['User-Agent' => 'JJImportMotors-Importnex/1.0 (jjimportmotors@gmail.com)'])
                    ->get('https://nominatim.openstreetmap.org/search', [
                        'q' => $city,
                        'format' => 'json',
                        'limit' => 1,
                        'addressdetails' => 0,
                    ]);

                if (! $response->successful()) {
                    // No cachear errores transitorios: null no se persiste.
                    Log::warning('GeoCoder: respuesta no satisfactoria', [
                        'city' => $city,
                        'status' => $response->status(),
                    ]);

                    return null;
                }

                $hit = $response->json('0');
                if (! is_array($hit) || ! isset($hit['lat'], $hit['lon'])) {
                    return 'none'; // 200 con 0 hits: ciudad desconocida (cache negativo)
                }

                return ['lat' => (float) $hit['lat'], 'lng' => (float) $hit['lon']];
            } catch (\Throwable $e) {
                Log::warning('GeoCoder: fallo geocoding', ['city' => $city, 'error' => $e->getMessage()]);

                return null; // no cachear: reintento en la próxima pasada
            }
        });

        return is_array($cached) ? $cached : null;
    }
}
