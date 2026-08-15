# Guía 07 — Solución de problemas (FAQ)

> Qué hacer cuando algo falla.

---

## 1. "No se puede conectar a la API" (401/503)

- **401** → token incorrecto o faltante. Verifica `X-Import-Token` y que `IMPORTNEX_CHAT_IMPORT_TOKEN` esté en el `.env` del servidor.
- **503** → el token no está configurado en el servidor. Configúralo en el `.env` y limpia caché.

```bash
php artisan config:cache
```

## 2. "El endpoint devuelve 500"

- Para `import-valuation`/`import-modelo`: si el error es de **validación de negocio** (ej. falta `precio_objetivo` en "Comprar si baja") ahora devuelve 422. Un 500 real indica error de servidor → revisa los logs.

```bash
# En el servidor
tail -f storage/logs/laravel.log
```

## 3. "No veo la página /kpis"

- Asegúrate de que el frontend está compilado (`npm run build` en el servidor tras el deploy).
- Verifica que las migraciones de `cierres` se ejecutaron.

## 4. "El filtro por marca no muestra nada"

- El filtro usa `brand` denormalizado en `cierres`. Los cierres **creados antes** de la migración `2026_08_12_100000_add_brand_model_to_cierres_table` no tienen `brand` → aparecen sin marca.
- Solución: al registrar nuevos cierres, incluye `brand` y `model`.

## 5. "Los KPIs salen vacíos / en 0"

- No hay cierres registrados en el periodo. Registra ventas (ver `06-cierre-venta`).
- Verifica que el periodo es el correcto (`YYYY-MM`).

## 6. "El IEDMT no me cuadra"

- El cálculo usa `config/iedmt.php` (coeficientes Anexo IV + tipos CO₂). Verifica que el coche tiene `co2` y `manual_tax_base`/`new_price` poblados.
- Si `boe_confirmed = true`, usa `new_price`; si no, `manual_tax_base`.

## 7. "mobile.de se queda colgado"

- Nunca 2 llamadas a mobile.de en el mismo `browser_batch`.
- `Runtime.evaluate` muere a 45s — usa `textContent`, no `innerText`.

## 8. "El skill me dice que la sincronización Desktop falla"

- Ejecuta `py .claude/skills/importacion-vehiculos/scripts/verify_desktop_sync.py` y revisa la salida. Si faltan scripts/datos, no arranques la sesión.

## 9. "¿Cómo hago backup de la BD antes de un cambio?"

```bash
# Local (Laragon)
"C:\laragon\bin\mysql\mysql-8.0.30-winx64\bin\mysqldump.exe" -u root importnex_saas > backup.sql
```

> **Siempre**: backup ANTES de cualquier INSERT/UPDATE/DELETE en producción.
