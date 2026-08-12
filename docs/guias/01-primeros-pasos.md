# Guía 01 — Primeros pasos

> Cómo empezar cada sesión con el skill `importacion-vehiculos`.

---

## 1. Arranque obligatorio (verificar sincronización Desktop)

**Antes de nada**, ejecuta la verificación de sincronización. Asegura que los scripts del Desktop están presentes y sincronizados.

```
py .claude/skills/importacion-vehiculos/scripts/verify_desktop_sync.py
```

- **Exit 0** → sesión OK, continúa.
- **Exit ≠ 0** → NO arranques: hay scripts o datos faltantes. Revisa la salida.

> Detalles en `operaciones.md` §Verificación de sincronización Desktop (ARRANQUE).

## 2. Presupuesto de peticiones (token budget)

El skill consume peticiones a portales (mobile.de, AutoScout24, Coches.net, etc.). **Lleva la cuenta mental** y avisa al usuario en estos umbrales:

| Flujo | Total máx | Avisar al 50% | Avisar al 80% |
|-------|-----------|---------------|---------------|
| A (unidad) | 70 | 35 | 56 |
| B (modelo) | 50 | 25 | 40 |
| C (mercado) | 100 | 50 | 80 |

**Regla dura:** nunca más de 45 peticiones a mobile.de en una sesión (avisar a 35).

## 3. Qué puedes pedirle al skill

| Dices... | Flujo | Qué hace |
|----------|-------|----------|
| "Evalúa este coche: <URL>" | A | Analiza UN coche concreto → veredicto de compra |
| "Busca oportunidades del <modelo>" | B | Investiga un modelo (mejores casos de compra) |
| "Escanea el mercado de <segmento>" | C | Escanea portales y detecta huecos de precio |
| "Registra que se vendió el Astra" | Cierre | Guarda la venta para los KPIs |
| "¿Cómo vamos de KPIs?" | KPIs | Resume precisión, tiempo de venta, desviación |

## 4. Qué NO hacer

- No pedir 2 llamadas a mobile.de en el mismo batch (bloquea).
- No iniciar sin la verificación de sincronización.
- No continuar si el presupuesto de peticiones se agota sin veredicto claro — pausa y pregunta.

## 5. Dónde ver los resultados

- **Informes PDF**: se adjuntan al expediente del coche en la app (ver `05-informes`).
- **Dashboard de KPIs**: `/kpis` en la web (autenticado).
- **Cierres**: vía API `/api/cierres` o el dashboard.
