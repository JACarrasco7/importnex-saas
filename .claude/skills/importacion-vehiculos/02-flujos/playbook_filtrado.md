# Playbook de filtrado — técnicas para Claude Desktop / VS Code

> Cargar cuando Claude tenga que **filtrar y buscar coches** en los portales con
> la extensión de navegador (Claude Desktop) o el **navegador integrado de VS Code
> (Playwright)**. Son técnicas concretas para ir rápido y no perder
> tokens en navegación inútil. Basado en `paginas_reales.md`.

---

## ⚡ Reglas de oro (leer PRIMERO)

1. **Empieza por el final:** antes de navegar, sabe qué dato necesitas (precio DE, precio ES, N anuncios, días publicado, CO₂). Cada captura cuesta tokens.
2. **Una URL bien construida ahorra 5 clics:** usa los parámetros de la URL cuando sea posible (ver tabla por fuente).
3. **Filtra en la página, no en la URL:** los filtros nativos (combobox Preis, Kilometer, Erstzulassung) son más fiables que los parámetros de URL y se aplican en vivo.
4. **Después de cada filtro → screenshot:** confirma que el contador cambió. Si no cambió, el filtro no se aplicó.
5. **Pareto:** 1 captura de página entera lee 10-20 tarjetas → no capturees tarjeta a tarjeta salvo fichas clave.
6. **Captcha/cookies:** si los ves, UN clic (Aceptar/Einverstanden) y seguir. Si persiste → fuente bloqueada, siguiente.
7. **DOBLE PASADA por potencia (CRÍTICO · 12-ago-2026):** el filtro por variante de texto (`OPC`, `GTI`, `M`...) se pierde coches genuinos mal etiquetados. SIEMPRE cruzar con una búsqueda por **kW/CV** (campo estructurado fiable). Detalle en §Doble pasada.
8. **Unión, no intersección:** al cruzar búsquedas, unir listas por ID de anuncio. Los que solo salen en la 2ª son los chollos escondidos.

---

## 🎯 Playbook por tipo de búsqueda

### Búsqueda A: "¿hay hueco para este coche?" (test rápido Fase 1)

**Objetivo:** en 6-8 capturas, saber mediana ES, mediana DE y días publicado.

```
1. mobile.de (1 navegación + 2 capturas)
   - URL con ms= + lang=de
   - Aceptar cookies → screenshot
   - Filtro Kilometerstand bis + Erstzulassung von (clic combobox) → screenshot
   - Anotar: <h1> "X Angebote" + 5-8 precios bajos + lista (kW/PS, km, año)

2. Coches.net (1 navegación + 2 capturas)
   - URL /segunda-mano/coches/<slug>
   - Filtro precio + provincia si aplica → screenshot
   - Anotar: contador tras filtro + 5-8 precios + etiquetas "Buen precio"

3. AutoUncle (1 navegación + 1 captura)
   - URL /es/coches-segunda-mano/<marca>/<modelo>
   - Anotar: "X coches" + días en venta de 3-4 anuncios
```

→ **Total: 5-6 capturas.** Tienes hueco (mediana ES vs DE) + rotación.

### Búsqueda B: "encuentra los 3 mejores candidatos DE" (top fichas)

```
1. mobile.de con filtros duros:
   - Erstzulassung von: año mín + 2 años del candidato
   - Kilometerstand bis: +40% del km del candidato
   - Preis bis: precio máximo de compra + 10%
   - Getriebe: si candidato es manual → Schaltgetriebe
   - Sort: "Preis (niedrigster zuerst)"
   → screenshot

2. Descartar rápido (visible en listado):
   - "NUR AN AUTOHÄNDLER" (si buscas particular)
   - "Unfallschaden" / "Nicht unfallfrei"
   - País no DE (NL/BE/LU → marcar, no descartar salvo cliente específico)
   - Modelo equivocado (validar vs título)

3. De los 15-25 primeros, clica 3 mejores → 1 captura por ficha:
   - Ficha: leer Fahrzeugdaten + Ausstattung + precio bruto/neto
   - Anotar: CO₂ si aparece, propietarios, historial

→ Total: 1 (filtros) + 1 (listado) + 3 (fichas) = 5 capturas.
```

### Búsqueda C: "modelo X, top 5 globales" (Flujo B)

```
1. mobile.de (buscador + filtros + listado) = 2 capturas → top 10 DE
2. AutoScout24.de (URL va_<version>) = 2 capturas → validar N total DE
3. AutoUncle = 1 captura → días publicado + bajadas precio
4. Coches.net (filtros + listado) = 2 capturas → mediana ES + priceRankIndicator

→ 7 capturas para Fase 1.
```

---

## � Doble pasada por potencia — NO perder coches mal etiquetados (CRÍTICO)

> **Falló en real 12-ago-2026 (Opel Astra OPC):** filtrar solo por variante `OPC` dio 72 anuncios pero un OPC genuino de 8.999 € NO salió porque su título era genérico "Opel Astra". El campo "variante" es texto libre del vendedor → no fiable. La potencia (kW) viene del permiso → campo estructurado fiable.

### Cuándo aplicarla
Siempre que la versión buscada sea un **tope de gama / acabado especial** que pueda estar mal etiquetado:
`OPC`, `GTI`, `GTD`, `R`, `M`, `AMG`, `RS`, `Type R`, `N`, `GTE`, `RS Line`, `Performance`, etc.

### Método (2 búsquedas + cruce)

```
PASO 1 — Búsqueda por variante de texto (la normal)
  URL/filtro: <marca>-<modelo> + variante "OPC"
  Resultado: 72 anuncios (muchos son "OPC-Line" o mal etiquetados)

PASO 2 — Búsqueda por MODELO BASE + potencia (kW)
  URL/filtro: <marca>-<modelo> SIN variante
  + Filtro Leistung/kW: [potencia_tope − 10 kW, potencia_tope + 5 kW]
    · Ej OPC 280 CV = 206 kW → filtrar 196–211 kW
  + Erstzulassung ≥ año_mínimo
  + Kilometerstand ≤ km_máximo
  Resultado: los OPC "disfrazados" de Astra normal

PASO 3 — CRUCE (unión, NO intersección)
  Unir ambas listas por ID de anuncio
  Eliminar duplicados
  Los que están SOLO en la búsqueda 2 = chollos escondidos
```

### Tabla de potencias para topes de gama habituales

| Modelo | CV | kW | Rango filtro kW |
|---|---|---|---|
| Opel Astra J OPC | 280 | 206 | 196–211 |
| VW Golf GTI (7/7.5) | 230-245 | 169-180 | 160–185 |
| VW Golf R | 300-320 | 221-235 | 212–240 |
| BMW M240i | 340 | 250 | 240–255 |
| Audi RS3 | 400 | 294 | 285–300 |
| Mercedes A45 AMG | 381-421 | 280-310 | 270–315 |
| Honda Civic Type R | 320 | 235 | 226–240 |

> ⚠️ Si no estás seguro de la potencia del modelo, búscala primero (km77/BOE o spec oficial) ANTES de la búsqueda 2. No inventes el rango.

### Aplicación por portal
- **mobile.de:** filtro `Leistung von/bis` (combobox o `ps` en URL)
- **AutoScout24.de:** filtro `Leistung` / `potencia`
- **Coches.net:** filtro `Potencia CV` (aunque el título diga otra cosa)
- **Resto:** aplicar misma lógica si el filtro de CV existe

### Coste extra
+2-3 capturas por portal (búsqueda 2 + cruce). **Vale la pena:** cada chollo escondido puede ser miles de € de margen.

---

## �🔧 Filtros potentes por fuente (qué clicar para acotar)

### mobile.de — filtros que valen oro
| Filtro | Cuándo | Cómo |
|---|---|---|
| **Preis bis** | Acotar a tu presupuesto | Combobox "bis" → preset o escribir |
| **Erstzulassung von** | Edad máxima | Combobox "von" → año |
| **Kilometerstand bis** | Km máx | Combobox "bis" |
| **Anbieter → Privatanbieter** | Solo particulares (chollos) | Radio en sección Anbieter |
| **Ausstattung → Schiebedach/Sitzheizung/Head-Up** | Equipamiento premium | Checkbox en sección Ausstattung |
| **Sortieren → Preis (niedrigster zuerst)** | Ver la base | Combobox arriba del listado |

**Quita el filtro "Beschädigte Fahrzeuge: Nicht anzeigen"** solo si buscas siniestros baratos para reexportar.

### AutoScout24.de — filtros útiles
- `fregfrom` (URL) o filtro "Erstzulassung von"
- Potencia: km/Leistung (kW) → acota para no ver versiones base
- **Sort: "Neueste zuerst"** → ver anuncios frescos (mejor negociables)
- "Bajada de precio reciente" si estuviera disponible

### Coches.net — atajos que merecen
- Rangos de precio预设: "hasta 10.000 €", "hasta 20.000 €" (clic directo)
- "Segunda mano particulares" → excluye concesionarios (chollos)
- "Etiqueta CERO/ECO" → filtra por DGT

### AutoUncle — sus superpoderes
- **Sort "Bajada de precio reciente"** → chollos negociables
- **Sort "En venta - Más antiguo"** → anuncios estancados (margen para regatear)
- Filtros: combustible, km, año, potencia

### Wallapop — maximizar muestra
- `order_by=price_asc` en URL (chollos arriba)
- `Page Down` 5-8 veces hasta agotar scroll
- Filtra por provincia si el cliente es local

### kleinanzeigen — descubrir chollos
- **Sort "Niedrigster Preis"** → chollos arriba
- Mirar **precio actual vs precio anterior** (bajada visible en tarjeta)
- Filtrar "Privat" en sidebar (solo particulares)

---

## 🔬 FILTRADO FINO Y LISTADOS ENGAÑOSOS (12-ago-2026)

> Lecciones del caso real (encargo 9.000 € · 2016+ · ≤150k · +120cv · gasolina · 5p):

### Conversión CV ↔ kW (mobile.de usa kW, España usa CV)
```
kW = CV × 0,7355   (también 1 PS ≈ 0,7355 kW)

Referencias rápidas:
  120 cv ≈ 88 kW     125 cv ≈ 92 kW     130 cv ≈ 96 kW
  140 cv ≈ 103 kW    150 cv ≈ 110 kW    200 cv ≈ 147 kW
```
- Si el cliente pide "+120 cv" → filtrar `Leistung von ≥ 88 kW`.
- Redondear SIEMPRE hacia abajo en el límite inferior (120 PS = 88,26 kW → 88).

### Filtro "5 puertas" (no siempre es directo)
- **mobile.de:** filtrar por carrocería: `Limousine` (berlina), `Kombi` (familiar), `Schrägheck` (5 puertas compacto). Descartar `Coupé`, `Cabrio`, `3-Türer`.
- **Coches.net:** checkbox carrocería (Berlina/Familiar/Compacto).
- Si el portal NO tiene filtro de puertas → filtrar por carrocería + validar en fichas (mirar "Türen" en mobile.de).
- **Nunca asumir 5 puertas por el nombre del modelo** (ej. un Coupé no lo es).

### Ordenar por precio → ignorar patrocinados
- Aplicar `Sortieren → Preis (niedrigster zuerst)`.
- Los **anuncios patrocinados** (suelen ser los primeros, caros o no reales) se IGNORAN — mirar los primeros resultados orgánicos.

### Bandas de precio — el listado NO es solo el más barato (15-ago-2026)
- **Fallo real (María, 9.000 €):** 526 resultados ordenados por precio ascendente, se leyó SOLO la página 1 → se enseñaron 8 coches de 3.000-4.200 € y se perdieron DS4, 308, Astra... que TAMBIÉN entraban en presupuesto.
- **Regla:** el listado para el cliente cubre TODO el rango válido (suelo → techo), no el extremo barato. Con muchos resultados, recorrer por **bandas** (ej. 3-5k / 5-7k / 7k-techo) o paginar hasta el techo (A12).
- Un coche de 7.500 € en presupuesto con mejor equipamiento puede ser MEJOR candidato que el de 3.750 €: el objetivo es el mejor **valor** del rango, no el precio mínimo.
- **Distinción D1 vs Flujo B (15-ago-2026):** en el sondeo D1 (enumerar qué modelos caben) NO se pagan todas las páginas — 2 lecturas por mercado: **asc** (suelo, página 1) + **desc** (techo, página 1), más facetas de marca con conteo y semilla `modelos-medidos.md`. La paginación/bandas completas son para Flujo B, donde se entregan candidatos con enlaces. El precio-desde de cada modelo sale de su primera aparición en asc/desc, no de paginar.
- **Año ensanchado (2016→2012) u otro filtro relajado:** declararlo ANTES de navegar y marcarlo en el informe (A13) — el usuario lo tolera, pero no en silencio.

### Anuncios engañosos (CRÍTICO · 12-ago-2026)
- **Síntoma:** precio anómalamente bajo en el listado (ej. 2.499 € para un coche de 2016+).
- **Causas:** coche siniestrado, fechas mal etiquetadas, error del vendedor, enganche.
- **Detección:** antes de dar un "precio desde", verificar que el anuncio tiene año/cv/km correctos y no está marcado como siniestrado. Si el mínimo es sospechoso, usar el 2º/3º orgánico.
- **Regla:** el "precio desde" de un modelo SIEMPRE sale de mobile.de (DE) o Coches.net (ES) verificado — NUNCA de AutoScout24 (A8) ni de un anuncio sin validar.

---

## ⏱️ Atajos de teclado (Claude los usa bien)

| Atajo | Para qué |
|---|---|
| `ctrl+l` (Win) / `cmd+l` (Mac) | Focus en barra de URL → pegar nueva URL |
| `Tab` | Moverse entre campos de filtro sin clic |
| `Enter` | Aplicar búsqueda/seleccionar opción combobox |
| `Page Down` / `End` | Scroll en listados infinitos (Wallapop) |
| `Escape` | Cerrar modales (cookies, popups) |
| `ctrl+f` | Buscar texto en la página (ej: "CO₂", "Unfallfrei") |

**Para dropdowns difíciles:** clic en el campo + `Tab` + flechas + `Enter` (más fiable que clic en opción).

---

## 🚦 Cuándo parar de navegar (anti-desperdicio)

| Señal | Acción |
|---|---|
| Captchas repetidos en misma fuente | Marcar bloqueada, siguiente fuente |
| Página no carga tras 2 intentos | Marcar caída, reintentar al final |
| Filtro no aplica tras 2 clics | Re-navegar con URL alternativa |
| <3 anuncios tras filtros duros | Aflojar filtros (km +20%, año -1) |
| Muestra ES <5 coches | No hay comparable sólido → EXIT 1 |
| Hueco DE-ES <8% | EXIT 1 (no sale) |

---

## 🎯 Detección de chollos (señales combinadas)

Un coche es **chollo priorizable** si tiene ≥3 de estas señales visibles:

1. **Etiqueta "Sehr guter Preis"** (mobile.de/AS24) o "Buen precio" (Coches.net)
2. **Días en venta >60** (AutoUncle/kleinanzeigen) → vendedor agotado, regateable
3. **Bajada de precio reciente** (AutoUncle %, kleinanzeigen precio anterior)
4. **"VB"** o "Verhandlungsbasis" (kleinanzeigen) → negociable explícito
5. **Privatanbieter** sin concesionario → sin margen comercial
6. **2. Hand** (mobile.de) o "1 Hand" → un dueño, suele cuidarse mejor
7. **TÜV NEU** o "ITV nueva" → gastado en homologar, señala buen estado
8. **Precio < cuartil bajo** del modelo

**Combinación ganadora:** etiqueta buen precio + días >60 + privado + VB → **CONTACTAR YA**.

---

## 🧠 Pensamiento antes de cada búsqueda

Antes de abrir el navegador, plantéate y di en voz alta:

```
1. ¿Qué flujo? (A/B/C/D) → define profundidad
2. ¿Qué fuentes tocan? (3 Fase 1 ó 7 Fase 2)
3. ¿Qué datos mínimos necesito? (precio, km, año, días publicado, CO₂)
4. ¿Cuál es mi budget de capturas? (A=70, B=50, C=100, D=8+embudo)
5. ¿Qué filtros aplicar primero para acotar?
```

Esto evita navegar a ciegas y gastar tokens de más.

---

## 📊 Plantilla de captura mental (lo que verás)

Cuando hagas screenshot, espera ver (móvil vs desktop):

| Tipo | Lo que ves en 1 captura |
|---|---|
| **Listado mobile.de** | 4-6 tarjetas con título + precio + sello + datos + vendedor |
| **Listado Coches.net** | 6-10 tarjetas con etiqueta precio + precio + datos + vendedor |
| **Listado AutoUncle** | 3-5 tarjetas con datos completos + días en venta + cambio precio |
| **Listado Wallapop** | 6-9 tarjetas limpias (título + año + km + cv + precio) |
| **Listado AS24.de** | 4-6 tarjetas con equipamiento listado + días publicado |
| **Listado kleinanzeigen** | 8-12 tarjetas con precio actual+anterior + km + EZ |
| **Ficha mobile.de** | Sección Fahrzeugdaten + Ausstattung + fotos |

Si una captura tiene menos datos de los esperados → probablemente la página no cargó bien. Recarga 1 vez.
---

## 📊 PRIORIZACIÓN POR ROI (movido de SKILL.md — Flujo B y C)

Cuando hay >3 modelos sin medir, aplicar scoring automático **antes** de empezar:

```
PRIORIDAD = MargenEstimado × VendibilidadEstimada × Urgencia
```

| Factor | Cálculo | Ejemplo |
|---|---|---|
| **MargenEstimado** | Ratio histórico del segmento | Nicho: 18%, Rotación: 10% |
| **VendibilidadEstimada** | Atractivo del modelo | Deportivo: 80, Premium: 60, Utilitario: 40 |
| **Urgencia** | ¿Hay cliente esperando? | Cliente concreto: 100, Sin cliente: 30, >1 mes sin medir: +20 |

**Tabla de priorización:**

| Modelo | Segmento | Margen est. | Vend. est. | Urgencia | PRIORIDAD |
|---|---|---:|---:|---:|---:|
| Golf 8 GTI CS | Nicho | 18% | 90 | 50 | **8.100** |
| Mercedes CLA | Rotación | 10% | 65 | 110 | **7.150** |
| BMW M240i | Nicho | 18% | 85 | 30 | **4.590** |
| Volvo XC60 T8 | Nicho | 15% | 70 | 30 | **3.150** |

**Regla:** Antes de cada sesión, puntuar los "sin medir" y proponer el top 3 al usuario.

---

## 🔗 DEDUPLICACIÓN ENTRE FUENTES (movido de SKILL.md)

**Problema:** Mismo coche en Wallapop y Milanuncios cuenta 2 veces. Infla la muestra.

**Solución:** Normalizar antes de contar:

```
Para cada coche en el pool:
  huella = (año, km_redondeado(±2%), cv, precio_redondeado(±3%), combustible)

Si huella ya existe → es duplicado → contar 1 vez, anotar fuentes
```

**Output:** "8 coches únicos en España (12 anuncios contando duplicados: 4 en Wallapop, 5 en Milanuncios, 3 en Coches.net)"

**Cuándo aplicar:**
- **Fase 1:** Después de recolectar Coches.net + mobile.de + AutoUncle
- **Fase 2:** Después de recolectar las 4 fuentes restantes
- **Flujo C:** Después de cada modelo escaneado

**Regla:** Si la huella coincide pero el precio difiere >10%, NO es duplicado (puede ser versión distinta).