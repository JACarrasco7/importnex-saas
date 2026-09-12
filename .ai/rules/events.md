---
glob: 'app/Events/**,app/Listeners/**,app/Observers/**'
title: 'Convenciones para eventos, listeners y observers'
---

## Por que esta regla existe (12-sep-2026)

El sistema de sync Laravel -> Desktop usa `App\Events\CarImported` + `App\Listeners\NotifyImportWebhook`.
Hubo bugs porque el listener era sincrono (bloqueaba el import si el webhook caia) y un
event() quedo muerto tras un return. Esta regla documenta el patron correcto.

## Convenciones

### Eventos (`app/Events/`)

- **Inmutables**: `public readonly` en todas las propiedades del constructor (PHP 8.2+ readonly).
- **Modelo del dominio**: si lleva una entidad Eloquent, pasar la instancia, no un array.
- **Dispatchables**: usan `Illuminate\Foundation\Events\Dispatchable` para poder despachar como `event(new X(...))` o `X::dispatch(...)`.
- **SerializesModels**: si llevan un modelo Eloquent, el trait serializa solo el id en cola y lo rehidrata al consumir.

```php
class CarImported
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly Car $car,
        public readonly string $flujo,
        public readonly string $carUrl,
        public readonly string $schemaVersion = '1',
    ) {}
}
```

### Listeners (`app/Listeners/`)

- **Si hace I/O (HTTP, mail, filesystem, queue externa) -> implementa `ShouldQueue`**. Asi no bloquea el request HTTP que dispara el evento.
- **Tries**: 3 por defecto para listeners HTTP externos. Para mail/notifications 5.
- **Backoff**: array `[5, 30, 120]` para HTTP (reintentos espaciados). Para mail, `[10, 60]`.
- **Timeout**: property `public int $timeout = 5;` para acotar jobs que pueden colgar.
- **Nunca lanzar al handler principal**: `try { ... } catch (\Throwable $e) { Log::warning(...) }`. El listener en cola: el job framework reintentara; el listener sincrono: debe morir silencioso para no romper el request.
- **⚠️ REQUISITO de deploy (ronda 3, 12-sep-2026):** `ShouldQueue` SOLO funciona si hay un **queue worker** corriendo (`php artisan queue:work` o daemon supervisord). En Forge esto es la pestana "Queues" del dashboard o un Daemon en supervisord. **Sin worker + `ShouldQueue` = los jobs se acumulan en `jobs` para siempre y nunca se ejecutan** = el listener nunca hace su trabajo y crece la tabla. Caso real: `NotifyImportWebhook` v3.9.3 uso `ShouldQueue` y empeoro el sistema porque Forge no tiene worker documentado. Verificar que el deploy tiene worker ANTES de marcar un listener como `ShouldQueue`.

```php
class NotifyImportWebhook implements ShouldQueue
{
    public int $tries = 3;
    public int $backoff = 5;
    public function handle(CarImported $event): void { ... }
}
```

**Si NO hay worker en produccion**, deja el listener sincrono con `try/catch` + timeout corto. Es peor en latencia (3s peor caso si el webhook remoto esta caido) pero predecible: o se ejecuta y notifica, o falla silencioso. NO se queda colgado en la tabla `jobs` para siempre.

### Despacho del evento

- **SIEMPRE antes del return**. Si lo pones despues es codigo muerto (PHP no ejecuta nada tras return).
- **Si el evento solo importa si la operacion tuvo exito**, despachalo dentro del try, justo antes del return final. NO en el catch (no tiene sentido notificar un fallo).
- **Verifica con `php -l` + tests que el archivo compila**. Un edit que rompe `}` = error de sintaxis que se commitea.

```php
// BIEN
try { $importer->apply($car, $payload); }
catch (...) { return response()->json([...], 422); }

event(new CarImported(car: $car, flujo: 'A', carUrl: url("/cars/{$car->id}")));
return response()->json([...], 201);

// MAL: event() muerto
return response()->json([...], 201);
event(new CarImported(...)); // <- nunca se ejecuta
```

### Registro de listeners

- **En Laravel 11 autodiscovery funciona**, pero declaralo en `AppServiceProvider::registerEventListeners()` para visibilidad y para soportar multiples listeners del mismo evento.
- **Un listener por evento por defecto**. Si necesitas varios, encapsula la logica en servicios separados y crea N listeners.

### Observers (`app/Observers/`)

- Solo para modelos Eloquent (`created`, `updated`, `deleted`, etc.).
- Si lo que necesitas es logica tras un comando o servicio -> evento, no observer.
- Registrar en `AppServiceProvider::registerObservers()` (no autodiscovery para observers).

## Trampas conocidas

- **Listener sincrono con I/O HTTP** = latencia en el request. Ejemplo real: `NotifyImportWebhook` sin `ShouldQueue` anadia hasta 3s al POST /api/import-valuation si el receptor estaba lento.
- **Event dispatch dentro del catch** = notificar un fallo que ya devolvio 4xx/5xx. No tiene sentido, confunde al receptor.
- **Listener que muta estado externo sin transaccion** = perdida de consistencia si falla a mitad.
