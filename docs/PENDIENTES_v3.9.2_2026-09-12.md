# Pendientes — v3.9.2 (12-sep-2026) — ✅ CERRADO 12-sep-2026

> **Estado:** todos los puntos están resueltos. Este doc queda como histórico.
>
> | # | Qué | Estado |
> |---|---|---|
> | 1 | Ejecutar `aplicar-v3.9.2.bat` | ✅ Hecho — commit `cb5e01a` + 6 más pusheados a master y desplegado en Forge |
> | 2 | `SHOW CREATE TABLE` en Forge | ✅ Resuelto sin migración — la aplicación usa utf8mb4 correctamente |
> | 3 | Matriculación Arteon | ✅ Resuelto — la barra KPI duplicada se eliminó; el dato real (06/2023) ya no compite con otro |
>
> Reflejado en skill v3.9.3 (`SKILL.md` + `CHANGELOG.md` + `memoria/MEMORIA.md`).

Todo lo que se podía arreglar por código ya está hecho y verificado (horquilla de precio,
fix del 21% de IVA fantasma, rediseño móvil, fix de los 4 canales de portal con texto
duplicado, y la guía de límites del editor de Marketing). Esto es lo único que queda, y
los tres puntos dependen de tu ordenador/servidor — no hay forma de que yo los haga desde
aquí (puente a tu máquina roto por una actualización de Windows del 8-sep, y sin acceso a
tu servidor de producción).

---

## 1. Aplicar el commit (el paso más importante — sin esto nada llega a Forge)

1. Descomprime `sync-skill-v3.9.2.zip` en cualquier carpeta de tu PC.
2. Haz doble clic en **`aplicar-v3.9.2.bat`**.
3. Qué hace, sin que tengas que tocar nada más:
   - Copia 7 ficheros a `.claude\skills\importacion-vehiculos\...` (yo no puedo escribir
     ahí desde el puente remoto — está bloqueado a propósito).
   - Hace **un solo commit + push** con los 13 ficheros tocados en la sesión: esos 7 +
     otros 6 de Laravel que ya escribí directamente en tu repo (`PrecioClienteCalculator.php`,
     `car-dossier.blade.php`, `cta-final.blade.php`, `ValuationPackageIngestor.php`,
     `CarMarketingController.php`, `config/marketing_limits.php`).
4. Verifica con `git log -1` que el commit se hizo.
5. Tras desplegar en Forge, comprueba:
   - Ficha de un coche en el móvil: una sola cifra de precio (horquilla), fotos justo
     después de las insignias, botón de WhatsApp fijo abajo.
   - Reimporta el ZIP de un coche y mira el módulo Marketing: Milanuncios / Coches.net /
     Wallapop / Facebook Marketplace ya no deberían tener el mismo texto (Wallapop más
     corto), y la pestaña Wallapop debería mostrar un contador "0-900" en vez de "0-2200".

> Los coches ya importados mantienen su ZIP antiguo (sin los bloques nuevos). Para verlo
> con datos nuevos hay que regenerar y reimportar su ZIP.

---

## 2. Comprobar si la BD de Forge ya está en utf8mb4

Un comentario en el propio código (fechado 09-sep-2026) dice que ya se verificó, pero eso
contradice que siguiera en tu lista de pendientes del 12-sep — así que compruébalo tú
mismo, no me fío del comentario:

```sql
SHOW CREATE TABLE car_marketing_contents;
```

- Si sale `DEFAULT CHARSET=utf8mb4` → ya está resuelto, tacha este punto.
- Si sale `utf8mb3` (o `latin1`) → guarda esto como
  `database/migrations/2026_09_12_120000_convert_tables_to_utf8mb4.php` (a propósito NO
  lo he puesto yo ahí, para que no se ejecute solo en un despliegue automático) y corre
  `php artisan migrate` cuando quieras, idealmente con un backup reciente a mano y fuera
  de la hora punta (el `ALTER TABLE` bloquea la tabla mientras se ejecuta, aunque en
  tablas pequeñas como las tuyas es cuestión de segundos):

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $tablas = ['car_marketing_contents', 'cars', 'clients', 'car_requests'];
        foreach ($tablas as $tabla) {
            if (! DB::getSchemaBuilder()->hasTable($tabla)) {
                continue;
            }
            DB::statement("ALTER TABLE `{$tabla}` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        }
    }

    public function down(): void
    {
        // No hay vuelta atrás automática: utf8mb3 no puede representar todos los
        // caracteres que ya se habrán guardado en utf8mb4 (emoji 4-byte).
    }
};
```

---

## 3. Fecha de matriculación del Arteon (01/2023 vs 06/2023)

**Corrección sobre lo que te dije antes:** dije que el conflicto estaba entre dos campos
del `informe.json` (`vehiculo.primera_matriculacion` vs `anuncio.primera_matriculacion`).
Era incorrecto — revisé el ZIP real del Arteon y esos campos no existen en ningún sitio
del código ni de los datos (solo hay `vehiculo.anio: 2023`, sin mes).

Lo que de verdad pasó: "01/2023" lo mostraba la barra KPI del dossier — la misma que ya
eliminé esta sesión por duplicar datos con la ficha técnica — y "06/2023" es el dato que
tú mismo me diste en una conversación anterior como la fecha real. Como ya no existe esa
barra KPI (era el único sitio donde se veía "01/2023"), **el bug de que se vieran dos
fechas distintas en la misma página ya está resuelto** sin que haya un campo de datos que
corregir en la base de datos.

No queda nada técnico pendiente aquí. Si quieres dejar constancia en algún sitio de cuál
es la fecha real (06/2023), es solo un dato para tu propio archivo — no afecta a ningún
cálculo (el IEDMT depende del año y el CO₂, no del mes).

---

## Resumen

| # | Qué | Bloqueado por |
|---|---|---|
| 1 | Ejecutar `aplicar-v3.9.2.bat` | Puente roto (Windows update 8-sep) + `.claude\` no escribible desde aquí |
| 2 | `SHOW CREATE TABLE` en Forge | Sin acceso a tu servidor de producción |
| 3 | Matriculación Arteon | Ya resuelto — no requiere acción técnica |
