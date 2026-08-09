# Informes — plan para ImportnexCore

**Fecha:** 9 de agosto de 2026 · Laravel 11 + Inertia 2 + Vue 3 + Tailwind 4
**Origen:** tres días midiendo a mano el Opel Astra J GTC OPC. Lo que sigue es la
estructura que hace que ese trabajo no se pierda, que se actualice solo y que el segundo
Astra cueste una décima parte que el primero.

---

## 1 · El problema ya está escrito en vuestros propios documentos

De `VALORACION_ESTADO.md`:

> *«El problema no es el formato. Es que **le pide a la IA que valore el precio de mercado
> sin darle ni un solo dato de mercado**. No hay búsqueda web, no se le pasan anuncios
> comparables, no se consulta ninguna base de recalls.»*

Y en lo pendiente de ese mismo documento:

> *«Marcar campos que requieren confirmación externa como pending. **Distinguir datos de
> memoria vs datos verificados**.»*

Las dos cosas están resueltas fuera de la app. Falta dónde guardarlas.

---

## 2 · Dos objetos, no uno

| | **INFORME** (`informes`) | **VEHÍCULO** (`cars`) |
|---|---|---|
| Qué es | Estudio de mercado de una versión | Una unidad concreta |
| Ejemplo | *Astra J GTC OPC 2.0T 280 CV* | *El OPC de 13.000 € de Wittmund* |
| Caduca | **Lento** — meses | **Rápido** — muere con el anuncio |
| Coste | Alto: es el 80 % del trabajo | Bajo, **si el informe ya existe** |
| Se reutiliza | **Sí, para cada unidad del modelo** | No |

Del trabajo del Astra, **el 80 % vale igual para el próximo OPC que aparezca**. Solo cambia
la unidad.

---

## 3 · Modelo de datos

### 3.1 · `informes`

```php
Schema::create('informes', function (Blueprint $t) {
    $t->id();
    $t->foreignId('organization_id')->constrained();

    // --- IDENTIDAD: la clave es la FICHA TECNICA, no el modelo comercial
    $t->string('slug')->unique();          // opel-astra-gtc-opc-280
    $t->string('make');                    // OPEL
    $t->string('make_norm')->index();      // opel      <- normalizado para el emparejado
    $t->string('model');                   // Astra
    $t->string('model_norm')->index();     // astra
    $t->string('version')->nullable();     // GTC OPC 2.0 Turbo
    $t->unsignedSmallInteger('power_cv')->index()->nullable();
    $t->string('fuel')->nullable()->index();
    $t->string('gearbox')->nullable()->index();
    $t->string('body')->nullable();
    $t->unsignedSmallInteger('year_from')->nullable();
    $t->unsignedSmallInteger('year_to')->nullable();

    // --- MERCADO (ultimo snapshot; el historico va en informe_snapshots)
    $t->json('spain_listings');
    $t->decimal('spain_median', 10, 2)->nullable();
    $t->decimal('spain_q1', 10, 2)->nullable();
    $t->decimal('spain_floor', 10, 2)->nullable();     // el mas barato creible
    $t->unsignedSmallInteger('spain_count')->nullable();
    $t->unsignedSmallInteger('spain_strict_count')->nullable();  // casan las 7 claves
    $t->json('germany_listings');
    $t->decimal('germany_median', 10, 2)->nullable();
    $t->decimal('germany_q1', 10, 2)->nullable();
    $t->unsignedSmallInteger('germany_count')->nullable();
    $t->unsignedSmallInteger('rotation_days_median')->nullable();

    // --- FICHA Y FISCALIDAD (km77 + BOE)
    $t->decimal('pvp_new', 10, 2)->nullable();
    $t->unsignedSmallInteger('co2')->nullable();
    $t->string('co2_cycle')->nullable();               // NEDC | WLTP
    $t->decimal('iedmt_rate', 5, 4)->nullable();
    $t->string('dgt_label')->nullable();
    $t->json('standard_equipment');                    // DE SERIE -> prima cero
    $t->json('optional_equipment');                    // opcion + precio -> si diferencia

    // --- RIESGO DEL MODELO
    $t->json('known_issues');
    $t->json('recalls');
    $t->decimal('modified_rate', 5, 4)->nullable();    // % de unidades preparadas vistas

    // --- SALUD Y FRESCURA
    $t->unsignedTinyInteger('health_score')->nullable();   // 0-100, calculado
    $t->json('sources');
    $t->timestamp('measured_at');
    $t->timestamp('expires_at');                       // measured_at + 90 dias
    $t->boolean('auto_refresh')->default(true);        // entra en el barrido diario
    $t->timestamps();
    $t->softDeletes();
});
```

### 3.2 · `informe_snapshots` — el histórico diario

**Esta tabla es la que más valor genera con el tiempo y la que hace posible el refresco
diario.** Cada medición guarda una foto; la diferencia entre fotos es información que hoy
no existe.

```php
Schema::create('informe_snapshots', function (Blueprint $t) {
    $t->id();
    $t->foreignId('informe_id')->constrained()->cascadeOnDelete();
    $t->date('measured_on')->index();
    $t->decimal('spain_median', 10, 2)->nullable();
    $t->decimal('spain_floor', 10, 2)->nullable();
    $t->unsignedSmallInteger('spain_count')->nullable();
    $t->decimal('germany_median', 10, 2)->nullable();
    $t->decimal('germany_q1', 10, 2)->nullable();
    $t->unsignedSmallInteger('germany_count')->nullable();
    $t->json('listing_ids');          // huella de los anuncios vivos ese dia
    $t->json('appeared');             // anuncios nuevos respecto al dia anterior
    $t->json('disappeared');          // <-- los que YA NO ESTAN
    $t->timestamps();
    $t->unique(['informe_id', 'measured_on']);
});
```

> **`disappeared` es el dato más valioso de todo el sistema.** Un anuncio que desaparece
> lo más probable es que **se haya vendido**. Con eso salen dos cosas que hoy son
> estimaciones: los **días reales hasta la venta** (mejor que los días publicado de los
> anuncios vivos, que sobrerrepresentan lo que no se vende) y, guardando el último precio
> visto, una aproximación al **precio de transacción**, que es el sesgo que hoy infla el
> margen entre 3 y 8 puntos.
>
> No es perfecto: un anuncio puede retirarse sin venderse. Pero **la mediana de días hasta
> desaparecer es infinitamente mejor que lo que tenemos hoy**, que es un solo dato.

### 3.3 · `car_valuations`

```php
Schema::create('car_valuations', function (Blueprint $t) {
    $t->id();
    $t->foreignId('car_id')->nullable()->constrained();
    $t->foreignId('informe_id')->nullable()->constrained();   // <-- LA UNION
    $t->enum('match_type', ['auto','manual','none'])->default('none');
    $t->unsignedTinyInteger('match_confidence')->nullable();  // 0-100

    $t->string('source_url');
    $t->string('source_portal');
    $t->string('seller_type');
    $t->decimal('price', 10, 2);
    $t->json('cost_breakdown');            // linea a linea con certeza y origen
    $t->decimal('final_price', 10, 2);
    $t->decimal('saving_vs_median', 10, 2)->nullable();
    $t->decimal('saving_vs_q1', 10, 2)->nullable();
    $t->json('saving_pct_range');          // [13.7, 28.6] - nunca una cifra sola
    $t->string('verdict');
    $t->text('verdict_reasoning');
    $t->unsignedTinyInteger('sellability')->nullable();
    $t->json('sellability_factors');
    $t->string('discard_reason')->nullable();
    $t->timestamps();
});
```

### 3.4 · `verification_items` — los sellos, accionables

```php
Schema::create('verification_items', function (Blueprint $t) {
    $t->id();
    $t->morphs('verifiable');              // informe | car_valuation
    $t->string('field');
    $t->string('label');
    $t->enum('certainty', ['ok','est','man','no'])->index();
    $t->text('action');
    $t->text('impact')->nullable();
    $t->string('source_url')->nullable();
    $t->boolean('resolved')->default(false);
    $t->text('resolution')->nullable();
    $t->foreignId('resolved_by')->nullable()->constrained('users');
    $t->timestamp('resolved_at')->nullable();
    $t->timestamps();
});
```

```php
enum Certainty: string {
    case Verificado  = 'ok';    // hay URL detras, se puede clicar
    case Estimado    = 'est';   // se conoce el metodo y el margen de error
    case Comprobar   = 'man';   // accion manual pendiente
    case Desconocido = 'no';    // nadie lo sabe sin ver el coche

    public function label(): string { ... }
    public function color(): string {   // clases Tailwind del tema
        return match($this) {
            self::Verificado  => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
            self::Estimado    => 'bg-amber-50 text-amber-700 ring-amber-600/20',
            self::Comprobar   => 'bg-red-50 text-red-700 ring-red-600/20',
            self::Desconocido => 'bg-slate-100 text-slate-600 ring-slate-500/20',
        };
    }
}
```

---

## 4 · Enlazado automático coche ↔ informe

Lo que pides: que se enlacen solos cuando se pueda.

```php
class InformeMatcher
{
    /** Devuelve [Informe|null, confianza 0-100, tipo] */
    public function match(Car $car): array
    {
        $q = Informe::query()
            ->where('organization_id', $car->organization_id)
            ->where('make_norm',  $this->norm($car->make))
            ->where('model_norm', $this->norm($car->model));

        // 1 · POTENCIA: es la clave 2 de la ficha y la que mas discrimina
        if ($car->power_cv) {
            $q->whereBetween('power_cv', [$car->power_cv - 8, $car->power_cv + 8]);
        }
        // 2 · Combustible y cambio: si no coinciden, NO es el mismo coche
        if ($car->fuel)    $q->where('fuel', $car->fuel);
        if ($car->gearbox) $q->where('gearbox', $car->gearbox);
        // 3 · Anio dentro del rango de la generacion
        if ($car->year) {
            $q->where(fn($w) => $w->whereNull('year_from')->orWhere('year_from','<=',$car->year))
              ->where(fn($w) => $w->whereNull('year_to')->orWhere('year_to','>=',$car->year));
        }

        $c = $q->get();
        if ($c->isEmpty())  return [null, 0, 'none'];
        if ($c->count() > 1) {
            // desempate por cercania de potencia y por frescura
            $c = $c->sortBy(fn($i) => abs(($i->power_cv ?? 0) - ($car->power_cv ?? 0))
                                      + ($i->isStale() ? 100 : 0))->values();
        }
        return [$c->first(), $this->confidence($car, $c->first()), 'auto'];
    }

    private function norm(?string $s): string {
        return Str::of($s ?? '')->lower()->ascii()->replaceMatches('/[^a-z0-9]+/','-')->trim('-');
    }
}
```

**Reglas:**

- **Coincidencia por ficha técnica, nunca por texto del título.** Marca + modelo + potencia
  ±8 CV + combustible + cambio + año dentro de la generación.
- **Confianza < 70 → no se enlaza solo**, se propone y lo confirma una persona.
  `match_type = 'manual'` cuando se acepta.
- **Sin informe → botón «Crear informe de este modelo»** que precarga marca, modelo,
  potencia, combustible y cambio del coche.

> **Por qué ±8 CV:** el mismo motor se homologa con cifras distintas según el año y el
> mercado. El Astra OPC aparece como 280 CV y como 206 kW (= 280,2). Un margen cerrado
> falla; uno ancho mezcla versiones. Ocho caballos es el hueco entre versiones reales.

---

## 5 · Actualización diaria

### 5.1 · El comando

```php
// app/Console/Commands/RefreshInformes.php
php artisan informes:refresh {--slug=} {--force} {--limit=20}
```

```php
// routes/console.php  o  app/Console/Kernel.php
Schedule::command('informes:refresh --limit=20')
    ->dailyAt('06:00')
    ->withoutOverlapping()
    ->onOneServer();
```

**Prioridad de la cola diaria**, para no gastar peticiones a lo tonto:

1. Informes con **unidades vivas valoradas** (hay dinero en juego)
2. Informes con **peticiones de cliente abiertas** (`car_requests`)
3. Informes **caducados** (`expires_at < now()`)
4. El resto, por antigüedad de `measured_at`

### 5.2 · Qué hace cada refresco

```
Por cada informe de la cola:
  1. Vuelve a medir Espania y Alemania con los extractores
  2. Compara contra el snapshot de ayer:
       - anuncios NUEVOS            -> appeared[]
       - anuncios QUE YA NO ESTAN   -> disappeared[]  (probable venta)
       - anuncios que HAN BAJADO    -> price_drops[]
  3. Recalcula medianas, cuartil bajo, suelo y rotacion
  4. Escribe informe_snapshots y actualiza informes
  5. Recalcula el ahorro de TODAS las car_valuations enlazadas
  6. Dispara eventos
```

### 5.3 · Eventos y avisos

Se enganchan al `/alerts` que ya existe:

| Evento | Cuándo | Por qué importa |
|---|---|---|
| `InformeCaducado` | `expires_at` pasado | No decidir con datos viejos |
| `MargenPerdido` | Una valoración cae por debajo del umbral | El mercado español ha bajado y no te has enterado |
| `UnidadDesaparecida` | El anuncio de una valoración activa desaparece | **Te lo han quitado.** Avisar el mismo día |
| `NuevaUnidadAlemana` | Aparece una por debajo del suelo del informe | Es la alerta que sustituye al barrido |
| `BajadaDePrecio` | Una unidad seguida baja | Momento de negociar |
| `PeticionCubierta` | Aparece algo que encaja con un `car_request` abierto | Cierra el bucle con el cliente |

> **`NuevaUnidadAlemana` es el cambio de modelo de trabajo.** Hoy sales a barrer cada
> cierto tiempo y ves lo que queda. Con esto el mercado te avisa a ti. El candidato del
> Astra llevaba 10 días publicado cuando lo encontramos; los buenos vuelan.

---

## 6 · Rutas y vistas

Bajo `auth + verified + organization`, como el resto:

| URL | Nombre | Vista |
|---|---|---|
| `/informes` | `informes.index` | `Informes/Index.vue` |
| `/informes/create` | `informes.create` | `Informes/Create.vue` |
| `/informes/{informe}` | `informes.show` | `Informes/Show.vue` |
| `/informes/{informe}/historico` | `informes.history` | `Informes/History.vue` |
| `/informes/{informe}/pdf` | `informes.pdf` | descarga |
| `/informes/{informe}/refresh` (POST) | `informes.refresh` | acción |
| `/cars/{car}/valuation` | `cars.valuation.show` | `Cars/Valuation.vue` |
| `/verification/{item}/resolve` (POST) | `verification.resolve` | acción |

### `Informes/Index.vue`

Filtros: **marca · modelo · versión · potencia · combustible · cambio · veredicto ·
frescura · salud**.

| Columna | Por qué |
|---|---|
| Modelo y versión | — |
| Uds. ES / DE | La escasez de un vistazo |
| Mediana ES · Suelo ES | Contra qué compites |
| **Rotación (días)** | Si rota o no |
| **Δ 30 días** | Flecha con el movimiento de la mediana. **Esto solo existe con snapshots** |
| Margen típico | Lo que dio la última unidad |
| **Salud** | Barra 0-100 |
| **Frescura** | Verde < 30 d · ámbar 30-90 · **rojo > 90** |

### La salud del informe

```php
public function healthScore(): int
{
    $frescura  = max(0, 100 - $this->measured_at->diffInDays(now()) * 1.1);  // 0 a los 90 d
    $muestra   = min(100, ($this->spain_strict_count / 8) * 100);            // 8 estrictos = 100
    $verificado= $this->verificationItems()->where('certainty','ok')->count()
               / max(1, $this->verificationItems()->count()) * 100;
    return (int) round($frescura * 0.35 + $muestra * 0.35 + $verificado * 0.30);
}
```

**Sirve para no engañarse.** Un informe de hace tres meses con tres comparables y la mitad
sin verificar no es un informe: es una corazonada con tablas. Que lo diga el número.

### `Informes/Show.vue`

Pestañas con las once secciones, más:

- **Unidades valoradas** — todas las `car_valuations` enlazadas con su veredicto y, si se
  cayeron, **el motivo textual**. Ese historial da el `modified_rate`.
- **Panel de verificación** con casillas que se marcan, quién y cuándo.
- **Histórico** — gráfica de mediana ES, mediana DE y nº de unidades. Se ve el mercado
  moverse.
- **Descargar PDF** — el que ya genera `tools/informe_importacion.py`.

### En la ficha del coche

```
┌─ INFORME DE MODELO ─────────────────────────── [VERIFICADO] [salud 82] ─┐
│  Astra J GTC OPC 2.0T 280 CV      medido hace 3 días                    │
│  8 uds. en España · mediana 22.559 € · suelo 15.900 € · rotación 65 d   │
│  ── Este coche ────────────────────────────────────────────────────────  │
│  Ahorro 13,7 % contra cuartil bajo        [3 verificaciones pendientes] │
│                                        [Ver informe]  [Ver valoración]  │
└─────────────────────────────────────────────────────────────────────────┘
```

### El componente de sello

```vue
<!-- resources/js/Components/SelloCerteza.vue -->
<span :class="['inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold ring-1 ring-inset', color]">
  {{ label }}
</span>
```

Se usa en **cada línea de coste, cada factor de vendibilidad y cada dato de la ficha**. La
regla que hace todo esto legible: **si un dato no lleva sello de COMPROBAR, es que hay una
fuente enlazada detrás.**

---

## 7 · Entrada de datos

Se amplía `/cars/import-valuation`, no se cambia:

```
paquete.zip
├── model_report.json     <-- el informe. Si existe y es fresco, se omite
├── valuation.json        <-- la unidad
├── descartadas.json      <-- las que se cayeron, CON EL MOTIVO
├── verification.json     <-- los pendientes
├── informe.pdf
└── fotos/
```

Emparejamiento por `slug`. Si existe y no ha caducado, se enlaza; si ha caducado, **nuevo
snapshot** y se conserva el anterior.

---

## 8 · Lo demás que se me ocurre

### 8.1 · Enlazar con `car_requests` — el bucle que falta

Ya tienes `/car-requests`. Enlazarlas a `informes` cierra el círculo:

- En el informe: **«2 personas esperando este modelo»**
- En la petición: **«Hay informe de este modelo, medido hace 3 días»**
- Y el aviso `PeticionCubierta` cuando aparece algo que encaja

**Esto convierte el barrido de «a ver qué encuentro» en «tengo gente esperando esto».** Es
el cambio de enfoque más rentable de toda la lista y cuesta una tabla pivote.

### 8.2 · Registro de descartes como activo

`discard_reason` acumulado da el dato que hoy no existe: *del Astra OPC, 2 de 13 alemanas
iban preparadas → 15 % de descarte.* Eso entra en el ranking del barrido y evita gastar
sesiones en modelos que siempre se caen. **Guarda los NO con el mismo cuidado que los SÍ.**

### 8.3 · Calibración del descuento por días publicado

Con `disappeared` y el último precio visto se puede empezar a medir el hueco entre precio
pedido y precio real. Tabla `market_calibrations` con el resultado por tramo de días. Hoy
es el sesgo que más infla el margen.

### 8.4 · Contenido, medido

«El contenido es el negocio» y no hay forma de saber qué publicación trajo preguntas.
Tabla `publications` enlazada al informe: modelo, fecha, plataforma, alcance y **preguntas
recibidas**. A los diez posts se ve qué mueve a *tu* audiencia, que no tiene por qué ser lo
que se vende bien en el mercado general.

### 8.5 · Condiciones de servicio — el riesgo que no está cubierto

El riesgo de este negocio es reputacional y no hay nada escrito. Cuatro cosas, una página:
quién responde si el coche llega dañado · qué se promete exactamente (localizado y
gestionado **no** es revisado en persona) · **señal y cancelación** — sin señal, el día que
un cliente se eche atrás con el coche ya comprado tienes capital inmovilizado, que es justo
lo que este modelo de negocio evita · quién paga si la ITV de importación falla.

### 8.6 · Alimentar el marketplace público desde el informe

El informe tiene ya el «por qué este coche» con datos. Puede generar el texto público
del marketplace. **Con el cortafuegos que ya está en la skill: en documentos de cliente,
ningún importe interno más allá del precio final y los honorarios.**

---

## 9 · Orden de implementación

| # | Qué | Esfuerzo | Desbloquea |
|---|---|---|---|
| 1 | Migraciones `informes` + `car_valuations` | 1 sesión | Todo lo demás |
| 2 | `ImportValuation` tragando el ZIP ampliado | 1 sesión | El Astra entra en la app |
| 3 | `Informes/Index.vue` con filtros | 1 sesión | Ver lo que tienes |
| 4 | `InformeMatcher` + bloque en la ficha del coche | 1 sesión | El enlazado automático |
| 5 | `verification_items` + `SelloCerteza.vue` | 1 sesión | Los sellos |
| 6 | `informe_snapshots` + `informes:refresh` diario | 1-2 sesiones | **Δ 30 días, desapariciones, rotación real** |
| 7 | Eventos y avisos sobre `/alerts` | 1 sesión | Dejar de barrer |
| 8 | `Informes/Show.vue` completo con histórico | 1-2 sesiones | — |
| 9 | Pivote con `car_requests` | Media | El bucle de demanda |

Del 1 al 5 ya da valor real. **El 6 es el que hace que el sistema aprenda solo**, y el 7 el
que cambia cómo trabajas.

---

## 10 · Una advertencia

**Esto no sustituye la investigación, la ordena.** Los informes se seguirán haciendo
midiendo portal a portal con `tools/extractores.js` y leyendo cada descripción entera — el
candidato que se cayó el 9 de agosto llevaba downpipe, limitador tocado y motor de
sustitución, y **eso solo apareció abriendo el anuncio original**. Ningún agregador lo
decía.

Y el sello de certeza no puede ser decorativo. El día que la app diga «ahorro del 27 %» sin
avisar de que ocho de cada diez cifras que lo sostienen son precios pedidos y no de venta,
habrá empeorado las cosas en lugar de mejorarlas.
