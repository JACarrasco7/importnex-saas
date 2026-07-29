# Plan — llevar la valoración enriquecida al SaaS

**Fecha:** 29 julio 2026
**Contexto:** revisión del proyecto tras decidir que Importnex sustituye al panel de Cowork como herramienta de trabajo, quedándose el chat solo para valoración e investigación.

---

## Lo que me he encontrado

El proyecto está bastante más avanzado de lo que refleja `PROGRESO.md`. Existen y funcionan: multi-tenancy con global scopes, CRUD de coches/clientes/contactos, dashboard con KPIs, mapa Leaflet, kanban, finanzas, planificador de viajes, fotos y documentos, plantillas de mensaje, suscripciones con Cashier, e importación CSV/XLSX.

**Y ya existe verificación con IA**, que `PROGRESO.md` da por pendiente (Fase 5, "0%"): están `CarVerificationController`, `CarVerificationService`, el job `VerifyCarWithAI`, la clave `ANTHROPIC_API_KEY` en `.env`, y hasta una capa de scraping con cuatro extractores (`GlmExtractor`, `MiniMaxExtractor`, `MistralExtractor` sobre `AiExtractorInterface`).

Así que el trabajo no es construir la valoración con IA desde cero. Es **subir de nivel la que ya hay**.

### El hallazgo que de verdad importa

`CarVerificationService::buildPayload()` monta un JSON con los datos del coche y se lo manda a Claude pidiéndole cinco campos planos: `traffic_light`, `valuation`, `recommendation`, `red_flags`, `tips`.

El problema no es el formato. Es que **le pide a la IA que valore el precio de mercado sin darle ni un solo dato de mercado**. No hay búsqueda web, no se le pasan anuncios comparables, no se consulta ninguna base de recalls. Se le entrega la ficha del coche y se le pregunta si el precio es justo en España.

Con eso, la IA solo puede responder de memoria. Para "¿este motor tiene averías conocidas?" eso da respuestas razonables. Para **"¿cuál es el precio de mercado hoy?"** y **"¿hay campañas de revisión abiertas?"** no: son datos que cambian y que hay que mirar. Una cifra inventada con aplomo es peor que no tener cifra, porque el semáforo del kanban se pinta con ella y las decisiones se toman mirando ese semáforo.

Esto no es un fallo de código —el código hace exactamente lo que se le pidió— sino una decisión de diseño que conviene corregir antes de meter datos reales.

### La otra brecha: el esquema es el antiguo

La tabla `cars` guarda la valoración así:

| Campo | Tipo |
|---|---|
| `valuation` | texto libre |
| `recommendation` | texto libre |
| `red_flags` | array de strings |
| `tips` | array de strings |
| `traffic_light` | string |
| `comparables_list` | json |

Es exactamente el esquema que teníamos antes en el panel y que sustituimos, por dos motivos concretos que se van a repetir aquí igual:

**Solo se registra lo malo.** Hay `red_flags` pero no hay lista de puntos a favor. Una ficha que solo enumera pegas no sirve para decidir, y menos para enseñársela a un cliente.

**No hay trazabilidad.** No se guarda de dónde sale cada hallazgo. Sin la URL de la fuente, ni tú ni un cliente podéis comprobar nada, y un dato inventado es indistinguible de uno verificado.

A eso se suma que un hueco vacío no distingue entre "no hay problema" y "no se miró", que no hay nivel de confianza (una valoración con el CO2 sin confirmar vale menos que una con el COC en la mano, y hoy las dos se muestran igual), y que no se guarda qué haría cambiar el veredicto, que es justo lo accionable: *"si baja 500 € pasa a comprar"*.

---

## Lo que propongo

Portar el modelo de valoración que ya está probado en el panel, adaptándolo a los nombres en inglés del SaaS. Concretamente:

- **9 aspectos investigados**, cada uno con hallazgo, **URL de fuente**, valoración (favorable/neutro/desfavorable) y fecha: averías comunes, recalls, precio de mercado, fiabilidad, homologación en España, etiqueta ambiental DGT, seguro estimado, piezas y mantenimiento, y otros.
- **Balance a favor / en contra**, cada punto con peso alto/medio/bajo.
- **Veredicto estructurado**: recomendación (comprar / comprar si baja / dudoso / descartar), confianza, razonamiento y qué lo cambiaría.
- **Mercado**: comparables reales con URL, media, mínimo, máximo y ahorro estimado frente a comprar en España.

Y separar con claridad **qué puede responder la IA de memoria y qué exige mirar la web**. Los cinco aspectos de conocimiento del modelo (averías, fiabilidad, etiqueta, seguro, piezas) puede rellenarlos la IA directamente. Los cuatro restantes (recalls, precio de mercado, homologación, y lo específico de esa unidad) necesitan datos frescos: o se le da búsqueda web, o se marcan explícitamente como pendientes de confirmar. Lo que no debe pasar es que salgan rellenos sin que nadie los haya mirado.

---

## Fases

### Fase A — Esquema (medio día)

Migración que añade a `cars`:

```
research          json      los 9 aspectos con fuente y valoración
pros              json      puntos a favor con peso
cons              json      puntos en contra con peso
verdict           string    Buy / Buy if price drops / Doubtful / Discard
verdict_confidence string   high / medium / low
verdict_reasoning text      el razonamiento en prosa
verdict_changes   text      qué haría cambiar el veredicto
verdict_at        timestamp cuándo se emitió
market_avg        decimal   media de comparables
market_min        decimal
market_max        decimal
estimated_saving  decimal   ahorro frente a comprarlo en España
research_source   string    'chat' | 'app' | 'manual'
schema_version    tinyint   versión del contrato con que se guardó
```

Los campos actuales (`valuation`, `recommendation`, `red_flags`, `tips`) **se dejan como están**. Hay datos y vistas colgando de ellos, y el modelo puede reconstruirlos desde los nuevos para que nada se rompa mientras se migra — el mismo patrón de compatibilidad hacia atrás que usamos en el panel.

Los 9 aspectos van en **una** columna JSON, no en nueve columnas. Son datos de lectura, casi nunca se filtran por SQL, y así añadir un décimo aspecto no obliga a migrar la tabla.

### Fase B — Entrada de datos desde el chat (medio día)

Comando `php artisan importnex:import-valuation` que lee los JSON del contrato ya definido (`schema_version`, `_meta.coche_id`, bloques `vehiculo`/`investigacion`/`balance`/`veredicto`/`costes`/`mercado`) y hace `updateOrCreate`.

El emparejamiento con el coche que ya existe en la base de datos se hace por VIN si lo hay, y si no por URL del anuncio; si no aparece ninguno, se crea nuevo. Conviene decidir esto antes de importar nada real (ver "Decisiones" al final).

La validación y el guardado van en métodos separados, no en línea dentro del comando, porque el día que la app esté en un servidor el endpoint HTTP reutiliza exactamente ese código y solo cambia de dónde viene el array.

### Fase C — Subir de nivel la IA de la app (2-3 días)

Reescribir el prompt de `CarVerificationService` para que devuelva la estructura enriquecida en vez de los cinco campos planos, y `parseAnalysis()` para leerla, **manteniendo el fallback actual**: si la respuesta no es JSON válido, hoy se guarda el texto crudo en `valuation` en vez de reventar, y eso está bien resuelto.

Aquí está la decisión de fondo: **si darle acceso web a la app o no**. Sin él, la app puede rellenar honestamente cinco de los nueve aspectos y debe marcar los otros cuatro como pendientes. Con él (búsqueda de comparables reales antes de llamar al modelo), puede rellenar los nueve. Es la diferencia entre una valoración orientativa y una que sostiene una decisión de compra.

Mientras tanto, el chat sí tiene búsqueda web y hace los nueve, así que el flujo mixto tiene sentido: la app da el primer filtro rápido, el chat hace la valoración de verdad para los coches que pasan el filtro.

### Fase D — Vistas (1-2 días)

En `Cars/Show.vue`, una pestaña de valoración con: el veredicto arriba con su color y su nivel de confianza, los 9 aspectos con enlace a la fuente, el balance a dos columnas, y el bloque de mercado con la posición del coche frente a los comparables.

Dos detalles que no son estéticos sino de fondo: **un aspecto sin hallazgo debe verse distinto de uno favorable** (hueco gris con "sin investigar", no un visto verde), y **la cifra del impuesto de matriculación debe mostrarse siempre como estimación**, porque Hacienda lo calcula sobre sus tablas oficiales de valor de mercado y no sobre lo que pague el cliente.

### Fase E — Limpieza (medio día)

`PROGRESO.md` está desactualizado y se contradice: da la Fase 5 por pendiente cuando está construida, dice "80%" arriba y "5.8%" en la tabla de métricas, y repite fases con estados distintos (la Fase 6 aparece como completada al 100% y como pendiente al 0% en la misma tabla). Conviene rehacerlo una vez y mantenerlo, o quitarlo: un documento de estado en el que no se puede confiar hace más daño que no tenerlo, porque se toman decisiones de planificación mirándolo.

---

## Decisiones que te corresponden

**1. ¿La app tendrá búsqueda web propia, o la investigación de verdad se queda en el chat?**
Es la decisión que más cambia el alcance. Si se queda en el chat, la Fase C se reduce mucho: la app solo hace el filtro rápido y marca lo que falta.

**2. ¿Cómo se emparejan los coches al importar?**
Mi recomendación es VIN primero, URL del anuncio después, y crear nuevo si no hay coincidencia. Pero muchos anuncios alemanes no publican el VIN, así que conviene confirmarlo antes de que haya datos reales de por medio.

**3. ¿Qué pasa con los campos antiguos?**
Propongo dejarlos y reconstruirlos desde los nuevos. La alternativa es migrarlos y borrarlos, que es más limpio pero rompe las vistas actuales hasta terminar la Fase D.

**4. ¿Los cuatro extractores de scraping siguen en juego?**
Hay tres implementaciones (GLM, MiniMax, Mistral) sobre una interfaz. Si en la práctica se usa solo una, mantener las otras es coste sin retorno.

---

## Lo que no toco

El multi-tenancy, la facturación, la autenticación y el CRUD funcionan y tienen tests. Nada de este plan los modifica: la valoración enriquecida se añade a `cars` sin tocar la lógica de organización ni los global scopes.
