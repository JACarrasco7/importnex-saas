# Importnex SaaS — Progreso de implementación

**Fecha:** 29 julio 2026
**Estado:** Plan completo + auditoría de inconsistencias cerrada.
**Plan vigente:** `PLAN_IMPLEMENTACION_COMPLETO.md`

---

## ✅ Lo que ya funciona (no se toca)

- Multi-tenancy con global scopes
- Auth con Breeze + middleware `organization`
- CRUD completo: coches, clientes, contactos, mensajes, alertas
- Dashboard, Kanban, Mapa Leaflet, Finanzas, Planificador
- Suscripciones con Cashier
- Importación CSV/XLSX + importación JSON del chat
- Subida de fotos y documentos
- Código 100% en inglés
- **Tests: 195 ✅ passed · 0 risky · 0 failed (757 assertions)**

---

## 🆕 Auditoría de inconsistencias (29/07)

| # | Inconsistencia | Severidad | Solución |
|---|---|---|---|
| 1 | Migration `2026_07_25_000005` tenía un bloque no-op para `client_contact_logs` (la columna la añade otra migration) | BAJA | Limpiado el bloque + comentario explicando de dónde viene la columna |
| 2 | `CarChecklistFactory` no incluía los nuevos campos `kind`/`priority`/`section` ni `organization_id` | MEDIA | Factory actualizada con todos los campos y constantes del modelo |
| 3 | `CarDocumentFactory` no incluía `doc_key`/`status`/`group` ni claves del expediente | MEDIA | Factory actualizada con las 17 claves reales + grupos + estados |
| 4 | `CarController::store()` y `update()` no aceptaban los nuevos campos enriquecidos (`verdict`, `research`, `pros`, etc.) | **ALTA** | Añadidos al `$request->only([...])` en ambas acciones |
| 5 | `FILESYSTEM_DISK=s3` en `.env` pero sin paquete AWS instalado (workaround con `public` disk en tests) | BAJA | OK — config tiene `throw=false`, tests usan `Storage::fake()`. Documentado en `PROGRESO.md` |
| 6 | `CarVerificationController` no escribe en los nuevos campos enriquecidos | BAJA | **Fuera de alcance** — el plan dijo que la IA de la app se queda como está. Solo se mantienen los 5 campos legacy (`traffic_light`, `valuation`, `recommendation`, `red_flags`, `tips`) |

### Test nuevo para validar #4
- `CarCrudTest::test_can_update_enriched_valuation_fields` — verifica que el form puede enviar y guardar `verdict`, `verdict_confidence`, `verdict_reasoning`, `market_avg/min/max`, `estimated_saving`, `pros`, `cons`.

---

## 📋 Estado del plan nuevo

| Fase | Estado |
|---|---|
| F1 — Esquema valoración | ✅ |
| F2 — Semáforo automático | ✅ |
| F3 — Checklist con listas fijas | ✅ |
| F4 — Documentos como expediente | ✅ |
| F5 — Puente con el chat | ✅ |
| F6 — Vistas | ✅ |
| F7 — Limpieza PROGRESO | ✅ |

---

## 📝 Notas operativas

- **IEDMT:** siempre se muestra como estimación. El cálculo ahora aplica % oficial por tramos de CO2.
- **Multi-tenancy:** todos los queries pasan por modelos con global scope.
- **Cashier:** `Organization` es `Billable`.
- **Importar informes:** JSON en `storage/app/importnex/import/` + `php artisan importnex:import-valuation --org="JJ Import Motors"`. El importer traduce ES → EN automáticamente.

---

**Última actualización:** 29 julio 2026 — Plan + auditoría cerrados. 195 tests passing.
