# `_sync` — dónde están las skills actualizadas y qué hacer con ellas

> Carpeta creada el 12-sep-2026. Claude deja aquí las skills actualizadas porque
> **no puede escribir bajo `.claude\`** (regla fija del puente remoto) ni ejecutar
> `git` (el puente de terminal está roto desde la actualización de Windows del 8-sep).

## Estado de las 3 copias de cada skill

Cada skill vive en TRES sitios y actualizar uno **no** actualiza los otros:

| Copia | importacion-vehiculos | estudio-mercado |
|---|---|---|
| **Repo** (`.claude\skills\`) | 3.9.2 ✅ | 0.3.12 → pendiente 0.4.0 |
| **Cowork / cuenta Claude** | 3.6.1 ❌ | 0.3.12 ❌ |
| **Claude Desktop** | sin confirmar | sin confirmar |

## Qué hacer (2 clics por skill)

### 1. Repo — `estudio-mercado` a 0.4.0
Doble clic en `estudio-mercado-v0.4.0\aplicar-estudio-mercado-v0.4.0.bat`.
Copia los 3 ficheros corregidos a `.claude\skills\estudio-mercado\` y hace commit+push.

(`importacion-vehiculos` ya está en 3.9.2 en el repo — no hace falta nada.)

### 2. Desktop + Cowork — importar los `.skill.zip`
En el gestor de skills de Claude Desktop y en las skills de tu cuenta, sube:
- `importacion-vehiculos-v3.9.2.skill.zip`
- `estudio-mercado-v0.4.0.skill.zip`

Así dejan de usar la 3.6.1 y la 0.3.12.

## Por qué importa que las tres estén iguales

Si Cowork usa la 3.6.1 y el repo la 3.9.2, un ZIP generado desde Cowork sale con la
lógica vieja (sin `[GASTO]`, sin canales de portal diferenciados) aunque el panel de
Laravel ya sepa leer el formato nuevo. La cadena solo funciona si las dos puntas van a la
misma versión.

## Cambio crítico de la 0.4.0 de estudio-mercado

`informe_mercado.md` afirmaba que un particular paga el 21 % de IVA de importación. Es
falso: en una compra intracomunitaria de un usado no se paga (salvo regla 6/6000: <6
meses o <6.000 km). Era el mismo fallo eliminado de `PrecioClienteCalculator.php`, y aquí
era peor porque un 21 % fantasma hunde el `hueco_neto_pct` y descarta modelos rentables.
Detalle completo en `docs\AUDITORIA_estudio-mercado_2026-09-12.md`.

`datos_mercado.json` está limpio (usa 1.129 € sin IVA) — no hay que re-medir nada.
