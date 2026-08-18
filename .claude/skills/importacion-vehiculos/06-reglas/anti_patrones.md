# Anti-patrones — Reglas duras del skill

> Cargar cuando Claude dude de si una práctica es correcta. Estas son **reglas duras** que bloquean errores históricos.
> Cualquier excepción debe justificarse EXPLÍCITAMENTE en el chat.

---

## 🛡️ Los 21 anti-patrones

| # | Anti-patrón | Regla dura |
|---|---|---|
| **A1** | Descartar por silencio | "Si el anuncio NO declara siniestro, NO asumir que lo tiene. Sello `man`, sigue en pool." |
| **A2** | Saltar mobile.de | "Si mobile.de no está en tabla cobertura con estado OK o bloqueada+intentos, NO hay veredicto." |
| **A3** | IEDMT sin fuente | "El CO₂ y PVP vienen de km77 o BOE. NUNCA de 'modelo similar'. Marcar `co2_confirmado: false` si estimado." |
| **A4** | Veredicto sin cuartil bajo | "Si ahorro contra cuartil bajo es negativo, veredicto de margen es NO, aunque mediana diga SÍ." |
| **A5** | Informe sin precio máximo | "Todo informe Flujo A incluye precio máximo de compra. Sin excepción." |
| **A6** | Tabla sin enlaces | "Toda tabla de candidatos incluye columna ENLACE clickable. Si no hay URL, se construye desde el ID. Los enlaces de comparables van SIEMPRE al anuncio directo (ficha del vehículo), NUNCA a una búsqueda/filtro del portal." |
| **A7** | Cobertura incompleta | "Siempre se intentan las 7 fuentes. Con <7 NO hay cifras ni veredicto: informe PARCIAL + preguntar al usuario." |
| **A8** | AutoScout24 como precio | "AutoScout24 NUNCA da precio de referencia (agrega feeds sin cribar). Solo para contar ofertas." |
| **A9** | Afirmar sin comprobar | "NUNCA decir 'sí lo vi en mi barrido' sin comprobarlo. Si no está en los datos capturados, digo que no está. Un falso positivo es peor que un falso negativo." |
| **A10** | Precio financiado como contado | "El precio grande de MUY CAR/Flexicar (y portales ES) suele ser el FINANCIADO, no el contado. Confirmar el contado antes de meterlo en la tabla: Milanuncios `price.cashPrice.value`, Coches.net/Wallapop abrir ficha y buscar 'contado'." |
| **A11** | Paginación parcial | "Coches.net ordena por relevancia, NO por precio. Para cliente concreto hay que recorrer TODAS las páginas con `pg=` filtrando por precio `pf=`. Si no se puede paginar todo, DECIRLO y marcar cobertura parcial." |
| **A12** | Página 1 como listado | "Ordenar por precio ascendente y leer SOLO la página 1 = sesgo hacia lo más barato/viejo (caso María 15-ago: 526 resultados, se enseñaron 8 de 3.000-4.200 € y se perdieron DS4/308/Astra). El listado del cliente cubre TODO el rango de presupuesto: todas las páginas o bandas de precio. Si se trunca, DECLARARLO. Matiz D1: en el sondeo de modelos (enumerar qué cabe) NO se pagina — bastan 2 lecturas (asc=suelo + desc=techo) + facetas de marca + semilla (ver SKILL.md §D1). La paginación completa es de Flujo B (candidatos con enlaces)." |
| **A13** | Filtros alterados sin declarar | "Cualquier cambio de los filtros del encargo se declara ANTES de navegar y se marca en el informe: tanto ampliar/relajar (año 2016→2012, km, precio) como usar un rango MÁS RESTRICTIVO que el aprobado (caso 15-ago: se aprobó 2012+ y se filtró 2016+). Nunca cambiar los criterios del cliente en silencio, en ningún sentido." |
| **A14** | Abandonar el camino en silencio | "El flujo es un camino numerado con waypoint 📍 en cada mensaje. Una pregunta lateral del usuario es una misión lateral: se responde y se RETOMA el paso (↩⃾ Vuelvo al paso N). Si tras una desviación el entregable de la fase no llegó, es un fallo. Un cambio de destino real se declara (🔀 Cambio de camino)." |
| **A15** | Sondeo D1 con búsqueda web | "El sondeo de modelos del Flujo D se hace SIEMPRE con navegación real a Coches.net + mobile.de con los filtros del encargo. La búsqueda web (snippets/agregadores) NO es método de sondeo: da cifras inconsistentes que contradicen la navegación real (caso 15-ago: Focus ES '~9.900 €' cuando la navegación real daba 3.000-6.990 €; 308 DE '10.980-12.600 €' sin confirmar). Degradado solo con portal bloqueado + reintentos (A2/A7)." |
| **A16** | Selección manual de modelos en D1 | "El sondeo es por FILTROS, no por modelo: una pasada con los filtros del encargo devuelve TODOS los modelos que caben. Prohibido elegir 3-4 a mano y dejar 'otros por explorar' sin sondear. Listar TODOS los que salen. La potencia es mínimo (≥Xcv) → versiones 125/130/150 valen igual; nunca sondear solo la variante tope." |
| **A17** | Abrir fichas en comparativas/listados | "Listado-first (17-ago-2026): en comparativas / Flujo C / Flujo E, trabajar con LISTADOS por defecto; abrir ficha de anuncio solo 2-3 unidades como ejemplo (las de mejor precio relativo, confirmado con el usuario). Prohibido perseguir el permalink de cada unidad — quema peticiones sin aportar. Aprovechar los sellos de precio visibles EN el listado (Coches.net: 'Buen precio'=4 / 'Precio justo'=3 · mobile.de/AS24: 'Sehr guter/Guter/Fairer Preis') para elegir las mejores muestras y entender el mercado sin abrir fichas. El detalle individual es de Flujo A/B, cuando el embudo es pequeño." |
| **A18** | Equipamiento inventado | "Solo publicar equipamiento VERIFICADO en la ficha real del anuncio; lo que viene del slug/título se marca 'por confirmar'. PROHIBIDO inventar equipamiento, sobre todo anacrónico (caso real 17-ago: MINI Cooper 2011 con 'Apple CarPlay', que no existía de fábrica en 2011). Contenido falso en publicaciones públicas = riesgo legal." |
| **A19** | Acotarse a los ejemplos del usuario | "Los modelos que el usuario nombra como ejemplo de una categoría NO limitan la búsqueda. Explorar TODO el segmento y añadir modelos no nombrados. NUNCA limitarse a los 5-6 ejemplos dados (caso real 17-ago: showstoppers se quedó en los 7 ejemplos, sin añadir i30N, Megane RS, etc.)." |
| **A20** | Mezclar búsqueda con marketing | "Buscar coches = informe de búsqueda con datos de mercado (nº anuncios, mediana, hueco). Generar anuncios/copy IG-FB/fichas de publicación SOLO si el usuario lo pide explícitamente DESPUÉS. NUNCA inventar el formato de publicación si el usuario solo pidió localizar coches (caso real 17-ago: se entregó un .docx lleno de copy IG/FB cuando el usuario solo quería buscar coches)." |
| **A21** | Entregar sin enlaces (anuncio + fuentes) | "TODO lo que se entregue lleva el enlace directo al anuncio (ficha del vehículo) y las fuentes con su URL. Candidatos, comparables, comparativas, informes, dossier, JSON y ZIP. Un dato sin su enlace NO se entrega como concluido. Es la regla que el usuario más repite: sin enlaces la entrega NO vale." |

---

## 🔍 Detalle de cada uno

### A21 — Entregar sin enlaces (anuncio + fuentes) (17-ago-2026)

**Error típico:** Claude entrega tablas de candidatos con precio/año/km pero sin las URLs de los anuncios, o informes sin la lista de fuentes consultadas con sus enlaces. El usuario no puede verificar nada y tiene que buscar cada coche a mano.

**Regla (la que el usuario más repite — NUNCA saltarla):**
1. **Cada candidato/comparable lleva SIEMPRE su enlace directo al anuncio** (ficha del vehículo, no búsqueda/filtro — A6). En tablas, en comparativas, en informes, en dossier, en JSON (`mercado.comparables[].url`) y en ZIP.
2. **Cada informe incluye la sección "Fuentes consultadas"** con todas las fuentes del flujo, su estado (OK / 0 resultados / bloqueada+intentos) y su enlace cuando aplique.
3. **Un dato/cifra/afirmación sin su enlace NO se entrega como concluido**: se declara cómo se obtuvo o se pide permiso para omitirlo.
4. **Al cerrar cualquier entrega, re-verificar con lupa**: si cualquier candidato, comparable o fuente carece de enlace, el trabajo está incompleto y hay que completarlo antes de entregar.

**Casos reales:**
- ❌ Tabla de top 5 sin columna de enlaces → el usuario no puede abrir los anuncios.
- ❌ Informe de unidad sin "Fuentes consultadas" → no se sabe qué se miró y qué no.
- ✅ Todo candidato con su ficha (`mobile.de/fahrzeuge/details.html?id=<id>`, slug Coches.net, `/app/item/<id>` Wallapop) + lista de fuentes al final.

---

### A1 — Descartar por silencio

**Error típico:** Anuncio no dice "unfallfrei" → Claude asume que tuvo siniestro.

**Regla:** El silencio NUNCA es descalificante. Solo se descarta por silencio + otras señales (precio muy bajo, vendedor B2B sin garantía, fotos inconsistentes). Una sola pieza de información faltante = `sello man` + acción ("preguntar al vendedor").

**Casos reales:**
- ❌ "No dice Unfallfrei" → descartado
- ✅ "No dice Unfallfrei" → `sello man` + "pedir COC o TÜV-Bericht"

---

### A2 — Saltar mobile.de

**Error típico:** Mobile.de da 403 o se bloquea. Claude sigue adelante con AS24 + AutoUncle y da veredicto.

**Regla:** mobile.de es la fuente **PRINCIPAL** para Alemania. Si no aparece en la tabla de cobertura con estado OK o bloqueada+intentos documentados, NO se emite veredicto. Se marca informe como **PARCIAL** y se pide al usuario decidir.

**Orden de reintento para mobile.de:**
1. `www.mobile.de` (en lugar de `suchen.` o `m.`) → recarga + espera
2. Intentar de nuevo con 2-3 s de pausa (captcha transitorio)
3. Solo entonces: marcar bloqueada en la tabla con los intentos documentados

---

### A3 — IEDMT sin fuente

**Error típico:** Claude estima CO₂ y PVP basándose en "modelos similares" o de cabeza.

**Regla:** El CO₂ y PVP deben venir de fuentes verificables:

| Fuente | Cuándo |
|---|---|
| **km77.com** | SIEMPRE intentar primero. Navegación normal (screenshot + lectura de la ficha de datos). |
| **BOE** Orden HAC/1501/2025 | Si km77 no tiene esa versión exacta. |
| **Estimación declarada** | Solo si 1 y 2 fallan. Marcar `co2_confirmado: false`. |

**NUNCA:**
- Usar CO₂ de "otro modelo similar"
- Usar PVP de cabeza o de otro acabado
- Dar IEDMT sin decir de dónde sale el CO₂

---

### A4 — Veredicto sin cuartil bajo

**Error típico:** La mediana da 19% de ahorro. Claude dice "COMPRA PRIORITARIA". El cuartil bajo da -8%. El cliente no compra porque nadie vende tan barato.

**Regla:** El veredicto de margen se toma **contra la mediana Y el cuartil bajo**. Si contra el cuartil bajo el ahorro es negativo, el veredicto de margen es **NO**, aunque la mediana diga SÍ.

**Por qué:** El cliente no compra la mediana — compara contra el coche más barato que puede conseguir en España en condiciones equivalentes. La mediana es el número del discurso comercial; el cuartil bajo y el suelo son los del veredicto.

---

### A5 — Informe sin precio máximo

**Error típico:** Claude da el desglose y el veredicto, pero no calcula el **precio máximo de compra** (lo que se puede pagar al alemán).

**Regla:** Todo informe de Flujo A incluye el precio máximo de compra, en una línea:

```
precio_max = comparable_objetivo × (1 − umbral)
           − transporte − ausfuhr − ITV/tasas − IEDMT − honorarios
```

**Por qué:** Cambia cómo se entra a negociar. Sin este número, no se sabe hasta cuánto pujar.

---

### A6 — Tabla sin enlaces

**Error típico:** Claude muestra una tabla de candidatos con precio/año/km pero sin URLs. El usuario tiene que buscar los anuncios manualmente.

**Regla:** Toda tabla de candidatos lleva columna **ENLACE** clickable. Si la fuente no da URL directa, se construye desde el ID:

---

### A7 — Cobertura incompleta (12-ago-2026)

**Error típico:** Se dan cifras y veredicto con solo 2-3 fuentes (ej. mobile.de + Coches.net) dejando Wallapop, Milanuncios, kleinanzeigen, AS24 sin mirar.

**Regla:** Se intentan SIEMPRE las 7 fuentes (ni más ni menos). Si alguna falla, reintentar 1-2 veces y luego marcarla `bloqueada (intentos)`. Con <7 fuentes NO se dan cifras ni veredicto → informe **PARCIAL** + preguntar al usuario si continúa o acepta PARCIAL.

---

### A8 — AutoScout24 como precio (12-ago-2026)

**Error típico:** Usar AutoScout24.de como referencia de precio. Agrega feeds de varios portales sin cribarlos → anuncios dañados/siniestrados y fechas mal etiquetadas se cuelan (caso real 12-ago: coche siniestrado a 2.499 € como "más barato").

**Regla:** AutoScout24 **solo sirve para contar** ofertas (N uds). NUNCA para precio de referencia. Precio DE = mobile.de; precio ES = Coches.net.

| Fuente | Construcción URL |
|---|---|
| mobile.de | `https://www.mobile.de/fahrzeuge/details.html?id=<id>` |
| Coches.net | Slug del anuncio en URL |
| AutoScout24 | `/lst/<marca>/<modelo>/<id>.htm` |
| AutoUncle | Enlace al portal de origen (sí lo trae) |
| Milanuncios | `/coches-de-segunda-mano/<id>.htm` |
| Wallapop | `/app/item/<id>` |

---

## ✅ Verificación rápida

Antes de cerrar cualquier informe, verificar:

- [ ] ¿Descarté algún candidato por silencio? (A1)
- [ ] ¿mobile.de está en la cobertura con OK o bloqueada+intentos? (A2)
- [ ] ¿CO₂ y PVP vienen de km77 o BOE? (A3)
- [ ] ¿Veredicto contra cuartil bajo también? (A4)
- [ ] ¿Informe Flujo A tiene precio máximo? (A5)
- [ ] ¿Todas las tablas tienen columna ENLACE? (A6)
- [ ] **¿TODO lo entregado lleva enlace al anuncio + fuentes con URL? (A21)**
- [ ] ¿Sondeo D1 con navegación real, no búsqueda web? (A15)
- [ ] ¿Informe D2 lista TODOS los modelos del filtro, sin "otros por explorar"? (A16)
- [ ] ¿En Flujo C/E trabajé con listados, no abriendo fichas de cada unidad? (A17)
- [ ] ¿El equipamiento publicado está VERIFICADO en la ficha, no inventado? (A18)
- [ ] ¿Exploré TODO el segmento, no solo los ejemplos del usuario? (A19)
- [ ] ¿No mezclé búsqueda con marketing (copy IG/FB)? (A20)

---

## 📚 Origen de cada anti-patrón

| # | Origen | Fecha |
|---|---|---|
| A1 | "El silencio no es descalificante" — corregido tras debate con usuario | 10-ago-2026 |
| A2 | Falla móvil.de durante sprint scraper | 10-ago-2026 |
| A3 | IEDMT erróneo por CO₂ estimado de similar | 09-ago-2026 |
| A4 | Astra OPC: veredicto positivo con cuartil bajo negativo | 09-ago-2026 |
| A5 | Regla histórica del skill original | pre-09-ago |
| A6 | "El usuario no puede ver el coche sin PDF" — feedback directo | 11-ago-2026 |
| A13 (ext) | Usar rango más restrictivo que el aprobado (2016+ tras aprobar 2012+) | 15-ago-2026 |
| A15 | Sondeo D1 con búsqueda web — datos inconsistentes vs navegación real | 15-ago-2026 |
| A16 | Selección manual de modelos en D1 ("otros por explorar") | 15-ago-2026 |
| A17 | Abrir fichas en comparativas/listados (stock: permalink de cada unidad) | 17-ago-2026 |
| A18 | Equipamiento inventado/anacrónico (MINI 2011 con "Apple CarPlay") | 17-ago-2026 |
| A19 | Acotarse a los ejemplos del usuario (no explorar el segmento completo) | 17-ago-2026 |
| A20 | Mezclar búsqueda con marketing (entregar copy IG/FB sin que lo pidan) | 17-ago-2026 |
| A21 | Entregar sin enlaces de anuncio/fuentes (la regla que el usuario más repite) | 17-ago-2026 |
