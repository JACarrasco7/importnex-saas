<?php

namespace App\Support;

use App\Models\Car;
use Illuminate\Support\Str;

/**
 * URLs de búsqueda "ver suelo" por portal, generadas desde los datos del coche.
 *
 * El ZIP ya trae `mercado.busquedas_realizadas[]`, pero los coches importados
 * antes del 09-sep-2026 (o los payloads manuales sin esa clave) no lo tienen:
 * este helper garantiza que la ficha del coche siempre ofrezca los enlaces para
 * comprobar el suelo a mano.
 *
 * Formato de cada portal: spec canónico de la skill `importacion-vehiculos`
 * (`02-flujos/playbook_filtrado.md` §"URL de resultados reales que SÍ funciona",
 * 24-ago-2026) + verificación propia en navegador (13-sep-2026).
 *
 * Todas las URLs salen ordenadas por PRECIO ASCENDENTE, que es justo lo que se
 * quiere al mirar el suelo:
 *   - mobile.de  -> `sb=p`
 *   - coches.net -> `fi=Price&or=1`
 *
 * Todas las URLs salen ordenadas por PRECIO ASCENDENTE, que es justo lo que se
 * quiere al mirar el suelo:
 *   - mobile.de  -> `sb=p`
 *   - coches.net -> `fi=Price&or=1`
 *
 * ── mobile.de (spec canonico: playbook_filtrado.md, 24-ago-2026) ──
 *   https://suchen.mobile.de/fahrzeuge/search.html?dam=0&fr=<a-1>%3A<a+1>
 *     &isSearchRequest=true&ms=<makeId>%3B<modelId>%3B%3B%3B&od=up&s=Car
 *     &sb=p&vc=Car&pw=<kW-4>%3A<kW+4>
 *   ⚠️ `pw` va en **kW**, NO en CV: kW = cv × 0,7355, margen ±4 kW.
 *   ⚠️ `ms` son CINCO campos (`make;modelo;;;`). Sin `modelId` la página cae en
 *      MODO FORMULARIO con 0 tarjetas → ahí se filtra por texto (`q=`).
 *   ⚠️ Los `modelId` de mobile.de CAMBIAN: solo se usan los verificados o los
 *      que ya vengan en las búsquedas reales del informe.
 *   ⚠️ NUNCA `www.mobile.de/es/...` (modo formulario sin tarjetas).
 *
 * ── coches.net (verificado en navegador 13-sep-2026) ──
 *   https://www.coches.net/segunda-mano/?MakeIds[0]=47&Versions[0]=Golf
 *     &PowerHpFrom=<cv-5>&PowerHpTo=<cv+5>&fi=Price&or=1
 *   → title "VOLKSWAGEN GOLF de segunda mano y ocasión | Coches.net"
 *   ⚠️ `PowerHp*` va en **CV**, margen ±5 CV.
 *   ⚠️ `ModelIds[0]` es preferible a `Versions[0]`, pero no hay mapa de
 *      ModelIds de coches.net: `Versions[0]` es el fallback sancionado, y solo
 *      con el nombre limpio del modelo (la variante rompe el filtro).
 *
 * ⚠️ Regla de negocio (`.ai/rules/mercado.md`): los enlaces a portales son
 * material INTERNO — solo panel admin, nunca marketplace público ni tracking
 * del cliente.
 */
class PortalSearchUrls
{
    /** Equivalencia que usa mobile.de (1 PS = 0,7355 kW). */
    private const KW_POR_CV = 0.7355;

    /** Margen del filtro `pw` de mobile.de (cubre redondeos del permiso alemán). */
    private const MARGEN_KW = 4;

    /** Margen del filtro `PowerHp*` de coches.net. */
    private const MARGEN_CV = 5;

    /**
     * makeId de mobile.de (tabla §"Tabla de IDs mobile.de", 24-ago-2026).
     *
     * ⚠️ Solo los vigentes. La tabla vieja de `empaquetar.py` traía IDs
     * duplicados o inventados (Hyundai y Nissan compartían `21000`, Volvo
     * repetía `25100`…) que devolvían la marca equivocada: peor que no filtrar.
     *
     * @var array<string, string>
     */
    private const MARCA_ID_MOBILE_DE = [
        'vw' => '25200', 'volkswagen' => '25200',
        'audi' => '1900',
        'bmw' => '3500',
        'mercedes' => '17200', 'mercedes-benz' => '17200',
        'seat' => '22500',
        'cupra' => '3',
        'opel' => '29000',
        'ford' => '24500',
        'hyundai' => '35500',
    ];

    /**
     * modelId de mobile.de, con clave `<makeId>:<primer token del modelo>`.
     *
     * ⚠️ mobile.de cambia estos IDs: añadir aquí SOLO los verificados con la
     * URL canónica. Los `ms` del informe del coche tienen prioridad sobre este
     * mapa.
     *
     * @var array<string, string>
     */
    private const MODELO_ID_MOBILE_DE = [
        '25200:golf' => '12603', // VW Golf Mk7.5 (verificado 24-ago-2026)
    ];

    /**
     * MakeIds de coches.net. VW=47 verificado en navegador (13-sep-2026).
     *
     * @var array<string, int>
     */
    private const MARCA_ID_COCHES_NET = [
        'vw' => 47, 'volkswagen' => 47,
        'bmw' => 11,
        'mercedes' => 12, 'mercedes-benz' => 12,
        'audi' => 4,
        'opel' => 7,
        'ford' => 5,
        'seat' => 9,
        'skoda' => 17,
        'renault' => 13,
        'peugeot' => 14,
        'citroen' => 15,
        'fiat' => 16,
        'honda' => 18,
        'hyundai' => 22,
        'kia' => 23,
        'mazda' => 24,
        'nissan' => 25,
        'toyota' => 10,
        'volvo' => 26,
        'cupra' => 27,
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
        $cv = (int) $car->cv;
        $portalesDelInforme = self::portalesDelInforme($car);
        $modeloId = self::modeloIdMobileDe($car, $marca);

        $enlaces = [
            [
                'portal' => 'mobile.de',
                'pais' => 'DE',
                'url' => self::mobileDe($marca, $modelo, $anio, $cv, $modeloId),
                'descripcion' => self::descripcion('Suelo DE', $marca, $modelo, $anio),
                'en_informe' => in_array('mobile.de', $portalesDelInforme, true),
            ],
            [
                'portal' => 'coches.net',
                'pais' => 'ES',
                'url' => self::cochesNet($marca, $modelo, $cv),
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
     * mobile.de, ordenado por precio ascendente.
     *
     * @param  string|null  $modeloId  modelId verificado (`2º` campo de `ms`)
     */
    private static function mobileDe(string $marca, string $modelo, int $anio, int $cv, ?string $modeloId): string
    {
        $makeId = self::MARCA_ID_MOBILE_DE[self::clave($marca)] ?? null;

        $params = ['dam' => '0'];

        if ($anio > 0) {
            $params['fr'] = ($anio - 1).':'.($anio + 1);
        }

        $params['isSearchRequest'] = 'true';

        if ($makeId !== null && $modeloId !== null) {
            $params['ms'] = $makeId.';'.$modeloId.';;;';
        } else {
            // Sin `modelId` verificado, `ms` deja la página en modo formulario
            // (0 tarjetas) → se filtra por texto libre, que sí da listado.
            $params['q'] = trim($marca.' '.$modelo);
        }

        $params['od'] = 'up';
        $params['s'] = 'Car';
        $params['sb'] = 'p';
        $params['vc'] = 'Car';

        if ($cv > 0) {
            $kwBase = (int) floor($cv * self::KW_POR_CV);
            $params['pw'] = ($kwBase - self::MARGEN_KW).':'.($kwBase + self::MARGEN_KW);
        }

        return 'https://suchen.mobile.de/fahrzeuge/search.html?'.http_build_query($params, '', '&');
    }

    /** coches.net, ordenado por precio ascendente (`fi=Price&or=1`). */
    private static function cochesNet(string $marca, string $modelo, int $cv): string
    {
        $params = [];

        $makeId = self::MARCA_ID_COCHES_NET[self::clave($marca)] ?? null;
        if ($makeId !== null) {
            $params['MakeIds[0]'] = $makeId;
        }

        $version = self::versionCochesNet($modelo);
        if ($version !== '') {
            $params['Versions[0]'] = $version;
        }

        if ($makeId === null && $version === '') {
            return '';
        }

        if ($cv > 0) {
            $params['PowerHpFrom'] = $cv - self::MARGEN_CV;
            $params['PowerHpTo'] = $cv + self::MARGEN_CV;
        }

        $params['fi'] = 'Price';
        $params['or'] = '1';

        return 'https://www.coches.net/segunda-mano/?'.self::queryConCorchetes($params);
    }

    /**
     * Año de matriculación. `year` se guarda como "MM/YYYY" (a veces "YYYY").
     */
    private static function anio(Car $car): int
    {
        return (int) substr((string) $car->year, -4);
    }

    /**
     * modelId de mobile.de para este coche.
     *
     * 1º las búsquedas reales del informe (IDs que Claude ya validó), 2º el mapa
     * de IDs verificados a mano. Si no hay ninguno, `null` → se usa texto libre.
     */
    private static function modeloIdMobileDe(Car $car, string $marca): ?string
    {
        $makeId = self::MARCA_ID_MOBILE_DE[self::clave($marca)] ?? null;

        if ($makeId === null) {
            return null;
        }

        return self::modeloIdDesdeInforme($car, $makeId)
            ?? self::MODELO_ID_MOBILE_DE[$makeId.':'.self::clave(self::primerToken($car->model))] ?? null;
    }

    /**
     * Extrae el `modelId` de un `ms=<makeId>;<modelId>;;;` que venga en las
     * búsquedas reales del informe.
     */
    private static function modeloIdDesdeInforme(Car $car, string $makeId): ?string
    {
        foreach ($car->busquedas_realizadas ?? [] as $busqueda) {
            if (! is_array($busqueda) || empty($busqueda['url'])) {
                continue;
            }

            $query = parse_url((string) $busqueda['url'], PHP_URL_QUERY);

            if (! is_string($query)) {
                continue;
            }

            parse_str($query, $params);
            $partes = explode(';', (string) ($params['ms'] ?? ''));

            if (($partes[0] ?? '') === $makeId && ($partes[1] ?? '') !== '') {
                return $partes[1];
            }
        }

        return null;
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

    /** Primer token alfanumérico del modelo (`Golf 7.5 TCR` -> `Golf`). */
    private static function primerToken(?string $modelo): string
    {
        $tokens = preg_split('/[^a-zA-Z0-9]+/', (string) $modelo) ?: [];

        foreach ($tokens as $token) {
            if ($token !== '' && preg_match('/[a-zA-Z]/', $token) === 1) {
                return $token;
            }
        }

        return '';
    }

    /**
     * Versión "limpia" para `Versions[0]`: primeras palabras del modelo hasta
     * el primer número (`Golf 7.5 TCR` -> `Golf`). La variante y la generación
     * dependen del etiquetado del vendedor y rompen el filtro.
     */
    private static function versionCochesNet(?string $modelo): string
    {
        $modelo = trim((string) $modelo);

        if ($modelo === '') {
            return '';
        }

        $partes = preg_split('/\s+/', $modelo) ?: [];
        $usadas = [];

        foreach ($partes as $parte) {
            if ($usadas !== [] && preg_match('/[0-9]/', $parte) === 1) {
                break;
            }

            $usadas[] = $parte;

            if (count($usadas) === 2) {
                break;
            }
        }

        return trim(implode(' ', $usadas));
    }

    private static function clave(?string $texto): string
    {
        return strtolower(trim(Str::ascii((string) $texto)));
    }

    /**
     * Como `http_build_query` pero deja los corchetes literales
     * (`MakeIds[0]=47`), que es como los espera coches.net.
     *
     * @param  array<string, int|string>  $params
     */
    private static function queryConCorchetes(array $params): string
    {
        $partes = [];

        foreach ($params as $clave => $valor) {
            $partes[] = $clave.'='.urlencode((string) $valor);
        }

        return implode('&', $partes);
    }
}
