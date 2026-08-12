# Guía 06 — Registrar ventas y ver KPIs

> Cada venta (o no-venta) alimenta los KPIs. Hazlo **siempre** para que el skill se calibre con datos reales.

---

## 1. Registrar un cierre

Dile al skill o usa la API directamente:

```bash
curl -X POST https://jjimportmotors.on-forge.com/api/cierres \
  -H "X-Import-Token: <token>" -H "Content-Type: application/json" \
  --data '{
    "coche_id": "opel-astra-opc-2012-38347146649056",
    "veredicto": "Comprar",
    "precio_objetivo": 11800,
    "fecha_venta": "2026-08-15",
    "precio_final": 11500,
    "cliente": "Juan Pérez",
    "plataforma": "Wallapop",
    "estado": "vendido"
  }'
```

**Campos obligatorios:** `coche_id`, `veredicto`, `fecha_investigacion`.
**Estado:** `vendido`, `no_vendido` o `pendiente` (default).
**Veredictos válidos:** `Comprar`, `Comprar si baja...`, `Dudoso`, `Descartar`.
**Opcionales (mejoran KPIs):** `brand`, `model`, `plataforma`, `car_id`.

> Regla de oro: pasados >30 días sin noticias, marca el cierre como `no_vendido`.

## 2. Ver los KPIs

**Dashboard web:** `/kpis` (autenticado). Muestra:
- Precisión de veredictos (objetivo ≥80%)
- Tiempo medio hasta venta (objetivo ≤15 días)
- Desviación media de precio (objetivo ≤5%)
- Tasa de falsos positivos (objetivo ≤20%)
- Tendencia de los últimos 6 meses
- Tabla de cierres con filtros por marca y plataforma

**API (histórico N meses):**
```bash
curl "https://jjimportmotors.on-forge.com/api/kpis?periodo=2026-08&months=6" \
  -H "X-Import-Token: <token>"
```

## 3. Cómo usar los KPIs

- **Precisión baja** (<80%) → el skill recomienda comprar coches que no se venden. Revisa los umbrales.
- **Tiempo de venta alto** (>15 días) → estás comprando coches lentos de vender.
- **Desviación alta** (>5%) → los precios finales se alejan del objetivo. Ajusta `precio_objetivo`.
- **Falsos positivos altos** (>20%) → demasiados "Comprar" fallidos.

## 4. Cierre de sesión

Al terminar cada sesión, confirma que los cierres pendientes están registrados y revisa un vistazo a `/kpis`.
