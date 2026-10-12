# Plan de implementación — cerrar Importnex para que funcione con todo ya

**Fecha:** 29 julio 2026
**Decisión de alcance:** la IA de la app (`CarVerificationService`, `VerifyCarWithAI`) se queda como está, sin tocar, para más adelante. La única fuente de valoración por ahora es el chat. Este plan cubre todo lo demás: lo que falta o está a medias en la app, y el puente que trae los informes del chat.

Sustituye y amplía a `PLAN_VALORACION_ENRIQUECIDA.md` (aquella Fase C, la de mejorar el prompt de la IA propia, queda fuera de alcance por ahora).

---

## De qué partimos

Ya funciona y no se toca: multi-tenancy con global scopes, CRUD de coches/clientes/contactos, dashboard, kanban, mapa Leaflet, finanzas, planificador de viaje, suscripciones con Cashier, importación CSV/XLSX, tests.

Lo que falta o está a medio construir, y que este plan resuelve:

| Pieza | Estado actual | Lo que falta |
|---|---|---|
| Investigación de 9 aspectos | No existe (solo `valuation`/`recommendation` en texto libre) | Esquema nuevo + vista |
| Balance a favor / en contra | Solo `red_flags`, sin lista de puntos buenos | Esquema nuevo + vista |
| Veredicto | Texto libre, sin confianza ni "qué lo cambiaría" | Esquema nuevo + vista |
| Comparables de mercado | JSON libre sin media/mín/máx calculados | Cálculo automático + vista |
| Semáforo (`traffic_light`) | Solo lo pone un formulario manual o la IA de la app | Recalculo automático contra el coste real |
| Checklist | Tabla genérica sin ninguna lista predefinida | 6 hitos + 76 puntos de inspección con prioridad |
| Documentos | Solo registro de archivos ya subidos, 6 tipos genéricos, sin estado | Expediente de 17 documentos con estado, aunque no se haya subido nada todavía |
| Puente con el chat | No existe | Comando de importación + contrato de formato |

---

## Fase 1 — Esquema de valoración enriquecida (1 día)

Migración sobre `cars` que añade:

```
research           json       9 aspectos {finding, source, rating, date}
pros               json       [{text, weight}]
cons               json       [{text, weight}]
verdict            string     Buy / Buy if price drops / Doubtful / Discard
verdict_confidence string     high / medium / low
verdict_reasoning  text
verdict_changes    text       que lo haria cambiar
verdict_at         timestamp
market_avg         decimal
market_min         decimal
market_max         decimal
estimated_saving   decimal
research_source    string     chat | manual
schema_version     tinyint    version del contrato con que se guardo
```

Los campos actuales (`valuation`, `recommendation`, `red_flags`, `tips`, `comparables_list`) se quedan tal cual — no se borra nada, no se rompe ninguna vista existente mientras se hace el resto del trabajo.

En el modelo `Car`, un accessor `researchGaps()` que devuelve qué aspectos no tienen `finding` — para poder avisar en la vista "faltan 3 aspectos por investigar" en vez de mostrarlos en blanco sin más contexto.

## Fase 2 — Semáforo automático (medio día)

Se crea `CarObserver` (registrado en `AppServiceProvider::boot()`) que, en el evento `saving`, recalcula `traffic_light` cada vez que cambian `market_avg` o cualquiera de los campos de coste:

```
precio total = calculateTotalCost() + professional_fees   (ya existe el método, solo se usa)
si precio total <= market_avg           → green
si precio total <= market_avg * 1.05    → amber
si no                                    → red
si no hay market_avg todavia            → se deja el valor actual, no se toca
```

Esto es el fallo que ya detectamos y arreglamos una vez en el panel — aquí es peor, porque hoy no se recalcula nunca solo. Es lógica de negocio pura, no necesita IA, así que entra en este plan aunque la IA de la app quede aparcada.

## Fase 3 — Checklist con listas fijas (1 día)

Migración sobre `car_checklists` que añade:

```
kind      string    milestone | inspection
priority  string    critical | important | minor   (solo aplica a "inspection")
```

Dos listas de constantes en `Car` (o en un `CarChecklistDefinitions` propio):

- **6 hitos** (kind=milestone): depósito pagado, transporte contratado, COC pedido, ITV hecha, IEDMT pagado, matriculado.
- **76 puntos de inspección** (kind=inspection), agrupados en 6 secciones — exterior, mecánica, interior, electrónica/diagnosis, prueba de carretera, negociación y cierre — cada uno con su prioridad.

Un evento `Car::created` (vía observer o el propio controller) crea las 82 filas de golpe con `completed=false`, para que la ficha nueva ya salga con la checklist completa en vez de vacía.

Los 4 hitos que tienen documento equivalente (COC pedido, ITV hecha, IEDMT pagado, matriculado) se marcan solos cuando el documento correspondiente pasa a "recibido" — mismo mecanismo que en el panel, va también en el observer de documentos de la Fase 4. Nunca desmarca lo que el usuario haya puesto a mano.

## Fase 4 — Documentos como expediente, no solo como archivos (1 día)

Hoy `car_documents` es un registro de subida (aparece una fila cuando se sube un archivo). Para que funcione como expediente hace falta que las 17 líneas existan **desde que se crea el coche**, aunque no se haya subido nada todavía — igual que en el panel.

Cambios:

```
url          nullable        (hoy es obligatorio; con esto una fila puede existir sin archivo aun)
doc_key      string          coc, teil_1, teil_2, purchase_contract, invoice, payment_proof... (17 claves fijas)
status       string          pending | ordered | received | not_applicable
group        string          seller_origin | purchase_transport | spain_procedures
```

`doc_type` (el campo que ya existe con 6 valores genéricos) se mantiene para compatibilidad, pero deja de ser el campo principal.

Mismo patrón que en checklist: al crear el coche se siembran las 17 filas en `pending`. Subir un archivo actualiza la fila existente (por `doc_key`) en vez de crear una nueva.

## Fase 5 — El puente: importar informes del chat (1 día)

Aquí es donde se conecta lo de antes con lo real. Comando `php artisan importnex:import-valuation`:

- Lee JSON de `storage/app/importnex/import/` (local, por ahora — el mismo camino que hablamos para tu carpeta, adaptado a rutas de Laravel en vez de Drive).
- Valida `schema_version` antes de nada — si sube de versión sin que el comando se haya actualizado, avisa y no importa a ciegas.
- Empareja con un coche existente por VIN si lo hay, si no por `url_link`; si no hay coincidencia, crea uno nuevo. **Esto lo tienes que confirmar tú**: muchos anuncios alemanes no dan el VIN, así que puede que la mayoría empareje por URL.
- Guarda en los campos de la Fase 1, dispara el recálculo de semáforo de la Fase 2 (al ser un `update` normal de Eloquent, el observer se activa solo).
- La validación y el guardado van en métodos separados del comando — el día que haya servidor, ese mismo código lo reutiliza el endpoint HTTP sin reescribir nada, solo cambia de dónde llega el array.

El contrato de formato (`_meta`, `vehiculo`, `anuncio`, `investigacion`, `balance`, `veredicto`, `costes`, `mercado`, `avisos`) es el mismo que ya dejé en `JJImportMotors/laravel/CONTRATO_EXPORT.md` — aquí solo cambian los nombres de columna de destino (inglés, los de `cars`), el contenido del JSON no se toca. Actualizaré ese documento para que apunte a este proyecto en vez del genérico.

## Fase 6 — Vistas (2 días)

En `Cars/Show.vue`:

- **Pestaña Investigación**: veredicto arriba (con color y confianza), balance a dos columnas, los 9 aspectos con enlace a la fuente — un aspecto sin `finding` se pinta como hueco gris "sin investigar", nunca como si estuviera bien.
- **Mercado**: comparables con su URL, y la posición del coste total frente a la media, mínimo y máximo.
- **Checklist**: separada en dos bloques — hitos arriba (progreso tipo "3/6"), inspección abajo agrupada por sección con su prioridad.
- **Documentos**: las 17 líneas agrupadas por fase (vendedor/país de origen, compra y traslado, trámites en España), con su estado y el botón de subida solo donde falte.
- Aviso permanente y visible de que el IEDMT es una estimación (Hacienda calcula sobre sus tablas oficiales, no sobre el precio pagado) — es fácil de olvidar y ya nos pasó una vez en el panel.

## Fase 7 — Limpieza de `PROGRESO.md` (medio día)

Se rehace una vez, sin las contradicciones actuales (80% arriba vs 5.8% en la tabla, la Fase 6 aparece completada y pendiente a la vez). Se añade el estado real de este plan según se vaya completando.

---

## Orden recomendado y por qué

Semáforo (Fase 2) antes que vistas: si se pinta mal en el kanban ahora mismo, más vale arreglarlo antes de construir encima. Checklist y documentos (Fases 3-4) pueden ir en paralelo con la Fase 1, no dependen entre sí. La importación (Fase 5) va después del esquema porque necesita las columnas ya creadas. Las vistas (Fase 6) van al final porque necesitan todo lo anterior para tener algo que mostrar.

Total estimado: **6-7 días** de trabajo efectivo.

---

## Decisiones que necesito antes de empezar

**1. Emparejamiento al importar** — VIN → URL → crear nuevo, ¿de acuerdo, o prefieres otra prioridad?

**2. Ruta de importación local** — ¿`storage/app/importnex/import/` te vale, o prefieres que sea la misma carpeta `JJImportMotors` de tu escritorio para no tener dos sitios donde mirar?

**3. Los 76 puntos y los 17 documentos** — ¿los defino calcando exactamente los del Excel/panel (mismos nombres, mismo agrupamiento), o quieres revisarlos primero por si alguno ya no aplica a como trabajáis ahora?

Si me confirmas esto empiezo por la Fase 1.
