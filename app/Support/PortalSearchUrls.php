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
     * Catálogo de IDs de mobile.de (marcas + modelos).
     *
     * ⚠️ Los IDs de mobile.de **caducan** (Golf Mk7.5 = `12603` daba 0 anuncios
     * el 13-sep-2026; el bueno es `14` = 57.717 anuncios). Por eso viven en un
     * fichero de datos fechado en vez de hardcodeados aquí.
     */
    private const CATALOGO_MOBILE_DE = __DIR__.'/data/mobile-de-catalogo.json';

    /** Caché del catálogo (se lee una vez por petición). */
    private static ?array $catalogo = null;

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
     * ModelIds de coches.net, con clave `<MakeIds[0]>` -> `<modelo normalizado>`.
     *
     * Es la forma PREFERIBLE; `Versions[0]` solo es el fallback para modelos sin
     * ModelId conocido. Golf = 89 verificado en navegador (13-sep-2026).
     *
     * @var array<string, array<string, int>>
     */
    private const MODELO_ID_COCHES_NET = [
        '47' => [
            'golf' => 89,
        ],
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
        $makeId = self::makeIdMobileDe($marca);
        $modeloId = self::modeloIdMobileDe($car, $makeId);

        $enlaces = [
            [
                'portal' => 'mobile.de',
                'pais' => 'DE',
                'url' => self::mobileDe($marca, $modelo, $anio, $cv, $makeId, $modeloId),
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
    private static function mobileDe(string $marca, string $modelo, int $anio, int $cv, ?string $makeId, ?string $modeloId): string
    {
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

        $modeloId = $makeId === null ? null : self::modeloIdCochesNet((string) $makeId, $modelo);

        if ($modeloId !== null) {
            $params['ModelIds[0]'] = $modeloId;
        } else {
            $version = self::versionCochesNet($modelo);
            if ($version !== '') {
                $params['Versions[0]'] = $version;
            }
        }

        if ($makeId === null && ! isset($params['Versions[0]'])) {
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
     * Catálogo de IDs de mobile.de, cacheado.
     *
     * @return array{marcas?: array<string, string>, modelos?: array<string, array<string, string>>}
     */
    private static function catalogo(): array
    {
        if (self::$catalogo === null) {
            $contenido = is_readable(self::CATALOGO_MOBILE_DE)
                ? (string) file_get_contents(self::CATALOGO_MOBILE_DE)
                : '';
            $datos = json_decode($contenido, true);
            self::$catalogo = is_array($datos) ? $datos : [];
        }

        return self::$catalogo;
    }

    private static function makeIdMobileDe(?string $marca): ?string
    {
        return self::catalogo()['marcas'][self::claveCatalogo($marca)] ?? null;
    }

    /**
     * modelId de mobile.de para este coche.
     *
     * 1º las búsquedas reales del informe (IDs que Claude ya validó), 2º el
     * catálogo de IDs verificados. Si no hay ninguno, `null` → texto libre.
     */
    private static function modeloIdMobileDe(Car $car, ?string $makeId): ?string
    {
        if ($makeId === null) {
            return null;
        }

        $delInforme = self::modeloIdDesdeInforme($car, $makeId);

        if ($delInforme !== null) {
            return $delInforme;
        }

        $modelos = self::catalogo()['modelos'][$makeId] ?? [];

        return self::buscarModelo($modelos, $car->model);
    }

    private static function modeloIdCochesNet(string $makeId, ?string $modelo): ?int
    {
        return self::buscarModelo(self::MODELO_ID_COCHES_NET[$makeId] ?? [], $modelo);
    }

    /**
     * Busca el ID de un modelo en un catálogo `<clave normalizada> => <id>`.
     *
     * @param  array<string, int|string>  $catalogo
     */
    private static function buscarModelo(array $catalogo, ?string $modelo): int|string|null
    {
        if ($catalogo === [] || $modelo === null) {
            return null;
        }

        foreach (self::clavesModelo($modelo) as $clave) {
            if (isset($catalogo[$clave])) {
                return $catalogo[$clave];
            }
        }

        // Último recurso: clave del catálogo que sea prefijo del modelo
        // (`320` para `320d`, `911` para `911 Urmodell`). Mínimo 2 caracteres
        // para no confundir siglas de una letra.
        $plano = self::claveCatalogo($modelo);

        foreach ($catalogo as $clave => $id) {
            if (strlen((string) $clave) >= 2 && str_starts_with($plano, (string) $clave)) {
                return $id;
            }
        }

        return null;
    }

    /**
     * Claves candidatas del modelo, de la más específica a la más genérica
     * (`Golf 7.5 TCR` → `golf75tcr`, `golf75`, `golf`).
     *
     * @return list<string>
     */
    private static function clavesModelo(?string $modelo): array
    {
        $plano = self::claveCatalogo($modelo);

        if ($plano === '') {
            return [];
        }

        $tokens = preg_split('/[^a-z0-9]+/', (string) strtolower(Str::ascii((string) $modelo))) ?: [];
        $tokens = array_values(array_filter($tokens, static fn (string $token): bool => $token !== ''));

        $claves = [$plano];

        if (count($tokens) > 2) {
            $claves[] = implode('', array_slice($tokens, 0, 2));
        }

        if ($tokens !== []) {
            $claves[] = $tokens[0];

            // `320d` → `320`: mobile.de agrupa el modelo sin la letra del motor.
            $sinLetra = (string) preg_replace('/[^0-9]+$/', '', $tokens[0]);
            if ($sinLetra !== '' && $sinLetra !== $tokens[0]) {
                $claves[] = $sinLetra;
            }
        }

        return array_values(array_unique($claves));
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

    /** Clave sin separadores para los catálogos (`Mercedes-Benz` → `mercedesbenz`). */
    private static function claveCatalogo(?string $texto): string
    {
        return (string) preg_replace('/[^a-z0-9]/', '', self::clave($texto));
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
