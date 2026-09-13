<?php

namespace App\Support;

use App\Models\Car;
use Illuminate\Support\Str;

/**
 * URLs de búsqueda "ver suelo" por portal, generadas desde los datos del coche.
 *
 * Es el port a PHP de `_url_mobile_de()` / `_url_coches_net()` de
 * `.claude/skills/importacion-vehiculos/scripts/empaquetar.py`. El ZIP ya trae
 * `mercado.busquedas_realizadas[]`, pero los coches importados antes del
 * 09-sep-2026 (o los payloads manuales sin esa clave) no lo tienen: este helper
 * garantiza que la ficha del coche siempre ofrezca los enlaces para comprobar
 * el suelo a mano.
 *
 * Todas las URLs salen ordenadas por PRECIO ASCENDENTE, que es justo lo que se
 * quiere al mirar el suelo:
 *   - mobile.de  -> `sb=p`
 *   - coches.net -> `fi=Price&or=1`
 *
 * Formatos verificados (12/15-ago-2026 y revalidados 13-sep-2026):
 *   - mobile.de: NUNCA `www.mobile.de/es/…` (cae en modo formulario sin
 *     tarjetas). La URL buena es `suchen.mobile.de/fahrzeuge/search.html`.
 *   - coches.net: el patrón es `/<marca>/<modelo>/segunda-mano/`. Si el slug de
 *     modelo no existe, coches.net degrada a la página de marca (nunca 404):
 *     `golf-7-5-tcr` -> "VOLKSWAGEN de segunda mano"; `golf` -> "VOLKSWAGEN Golf
 *     de segunda mano". NUNCA usar `Versions[]`/`Version=`.
 *
 * ⚠️ Regla de negocio (`.ai/rules/mercado.md`): los enlaces a portales son
 * material INTERNO — solo panel admin, nunca marketplace público ni tracking
 * del cliente.
 */
class PortalSearchUrls
{
    /**
     * ID de marca en mobile.de (segmento `ms=<id>;;;`).
     *
     * @var array<string, string>
     */
    private const MARCA_ID_MOBILE_DE = [
        'vw' => '25200', 'volkswagen' => '25200',
        'bmw' => '3500',
        'mercedes' => '17200', 'mercedes-benz' => '17200',
        'audi' => '1900',
        'opel' => '47000',
        'ford' => '9000',
        'seat' => '12200',
        'skoda' => '2400',
        'renault' => '7300',
        'peugeot' => '5900',
        'citroen' => '5000',
        'fiat' => '4000',
        'honda' => '11000',
        'hyundai' => '21000',
        'kia' => '22300',
        'mazda' => '16800',
        'nissan' => '21000',
        'toyota' => '24100',
        'volvo' => '25100',
        'cupra' => '25900',
        'ds' => '19500',
    ];

    /**
     * Alias de marca para el slug de coches.net. El resto se usa tal cual
     * (normalizado a minúsculas, con `_` en marcas compuestas: `alfa_romeo`).
     *
     * @var array<string, string>
     */
    private const MARCA_SLUG = [
        'vw' => 'volkswagen',
        'mercedes' => 'mercedes-benz',
        'land rover' => 'land_rover',
        'alfa romeo' => 'alfa_romeo',
        'aston martin' => 'aston_martin',
        'rolls royce' => 'rolls_royce',
    ];

    /**
     * Enlaces "ver suelo" del coche, uno por portal con precio de referencia.
     *
     * @return list<array{
     *     portal:string,
     *     pais:string,
     *     url:string,
     *     descripcion:string,
     *     en_informe:bool
     * }>
     */
    public static function forCar(Car $car): array
    {
        $marca = trim((string) $car->brand);
        $modelo = trim((string) $car->model);

        if ($marca === '' || $modelo === '') {
            return [];
        }

        $anio = self::anio($car);
        [$cvMin, $cvMax] = self::bandaCv($car);
        $portalesDelInforme = self::portalesDelInforme($car);

        $enlaces = [
            [
                'portal' => 'mobile.de',
                'pais' => 'DE',
                'url' => self::mobileDe($marca, $anio, $cvMin, $cvMax),
                'descripcion' => self::descripcion('Suelo DE', $marca, $modelo, $anio),
                'en_informe' => in_array('mobile.de', $portalesDelInforme, true),
            ],
            [
                'portal' => 'coches.net',
                'pais' => 'ES',
                'url' => self::cochesNet($marca, $modelo, $anio, $cvMin, $cvMax),
                'descripcion' => self::descripcion('Suelo ES', $marca, $modelo, $anio),
                'en_informe' => in_array('coches.net', $portalesDelInforme, true),
            ],
        ];

        return array_values(array_filter(
            $enlaces,
            static fn (array $enlace): bool => $enlace['url'] !== ''
        ));
    }

    /**
     * @return array<string, string>
     */
    private static function mobileDe(string $marca, int $anio, int $cvMin, int $cvMax): string
    {
        $params = [
            'dam' => '0',
            'isSearchRequest' => 'true',
            'od' => 'up',
            's' => 'Car',
            'sb' => 'p',
            'vc' => 'Car',
        ];

        if ($anio > 0) {
            $params['fr'] = ($anio - 1).':'.($anio + 1);
        }

        $makeId = self::MARCA_ID_MOBILE_DE[strtolower($marca)] ?? null;
        if ($makeId !== null) {
            $params['ms'] = $makeId.';;;';
        }

        if ($cvMin > 0 && $cvMax > 0) {
            $params['pw'] = $cvMin.':'.$cvMax;
        }

        return 'https://suchen.mobile.de/fahrzeuge/search.html?'.http_build_query($params, '', '&');
    }

    /**
     * URL de coches.net ordenada por precio.
     *
     * Patrón verificado en navegador (13-sep-2026):
     *   `https://www.coches.net/<marca>/<modelo>/segunda-mano/?fi=Price&or=1`
     *   -> title "VOLKSWAGEN Golf de segunda mano y ocasión | Coches.net"
     */
    private static function cochesNet(string $marca, string $modelo, int $anio, int $cvMin, int $cvMax): string
    {
        $marcaSlug = self::slugMarca($marca);

        if ($marcaSlug === '') {
            return '';
        }

        $ruta = 'https://www.coches.net/'.$marcaSlug.'/';
        $modeloSlug = self::slugModelo($modelo);

        if ($modeloSlug !== '') {
            $ruta .= $modeloSlug.'/';
        }

        $ruta .= 'segunda-mano/';

        $params = [];

        if ($cvMin > 0 && $cvMax > 0) {
            $params['PowerHpFrom'] = $cvMin;
            $params['PowerHpTo'] = $cvMax;
        }

        if ($anio > 0) {
            $params['MinYear'] = $anio - 1;
        }

        $params['fi'] = 'Price';
        $params['or'] = '1';

        return $ruta.'?'.http_build_query($params, '', '&');
    }

    /**
     * Año de matriculación. `year` se guarda como "MM/YYYY" (a veces "YYYY").
     */
    private static function anio(Car $car): int
    {
        return (int) substr((string) $car->year, -4);
    }

    /**
     * Banda de potencia (±10%) para comparar unidades equivalentes y no el
     * suelo de otra motorización del mismo modelo.
     *
     * @return array{0:int, 1:int}
     */
    private static function bandaCv(Car $car): array
    {
        $cv = (int) $car->cv;

        if ($cv <= 0) {
            return [0, 0];
        }

        return [(int) floor($cv * 0.9), (int) ceil($cv * 1.1)];
    }

    /**
     * Portales para los que el informe ya trae una búsqueda real.
     *
     * @return list<string>
     */
    private static function portalesDelInforme(Car $car): array
    {
        $portales = [];

        foreach ($car->busquedas_realizadas ?? [] as $busqueda) {
            if (is_array($busqueda) && ! empty($busqueda['url']) && ! empty($busqueda['portal'])) {
                $portales[] = strtolower((string) $busqueda['portal']);
            }
        }

        return $portales;
    }

    private static function descripcion(string $prefijo, string $marca, string $modelo, int $anio): string
    {
        $texto = $prefijo.': '.trim($marca.' '.$modelo);

        if ($anio > 0) {
            $texto .= ' · '.($anio - 1).'-'.($anio + 1);
        }

        return $texto.' · más baratos primero';
    }

    /** Slug de marca de coches.net (`alfa_romeo` usa guión bajo). */
    private static function slugMarca(string $marca): string
    {
        $clave = strtolower(trim(Str::ascii($marca)));
        $clave = self::MARCA_SLUG[$clave] ?? $clave;

        return trim(preg_replace('/[^a-z0-9]+/', '_', $clave) ?? '', '_');
    }

    /**
     * Slug de modelo de coches.net: solo el primer token (`Golf 7.5 TCR` →
     * `golf`), que es la forma que coches.net reconoce. Si el token es
     * puramente numérico (`3 Series`) se usan los dos primeros.
     */
    private static function slugModelo(string $modelo): string
    {
        $tokens = preg_split('/[^a-z0-9]+/', strtolower(Str::ascii($modelo))) ?: [];
        $tokens = array_values(array_filter($tokens, static fn (string $token): bool => $token !== ''));

        if ($tokens === []) {
            return '';
        }

        $usados = preg_match('/^[0-9]+$/', $tokens[0]) === 1
            ? array_slice($tokens, 0, 2)
            : array_slice($tokens, 0, 1);

        return implode('-', $usados);
    }
}
