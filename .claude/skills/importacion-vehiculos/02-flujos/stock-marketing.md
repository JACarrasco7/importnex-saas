# Flujo E · STOCK — búsqueda de coches por categorías (SIN marketing)

> **Disparador:** "stock recurrente", "catálogo bajo pedido", "busca coches por categorías", "qué modelos merecen la pena por segmento".
> **Naturaleza:** es **BÚSQUEDA de coches reales** organizada por categorías/segmentos. NO es comprar stock — es ofertar catálogo bajo pedido.
> **Creado 17-ago-2026** tras el fallo real: la IA mezcló "buscar coches" con "generar anuncios" y entregó publicaciones IG/FB que NADIE pidió.

---

## ⚠️ Reglas duras del Flujo E (leer PRIMERO)

0. **ESTO ES BÚSQUEDA, NO MARKETING.** El entregable es un **informe de búsqueda con datos de mercado** (nº anuncios, mediana, hueco, precios verificados). **NO se generan anuncios, copy de Instagram/Facebook, ni fichas de publicación.** El marketing es un flujo SEPARADO que solo se activa si el usuario lo pide explícitamente DESPUÉS ("ahora hazme los anuncios"). Fallo real 17-ago: se entregó un .docx lleno de copy IG/FB y fichas marketplace cuando el usuario solo quería localizar coches.
1. **Los ejemplos del briefing son ILUSTRATIVOS, no lista cerrada (A19).** "Golf GTI/R, Cupra, Arteon..." son ejemplos de categoría. La IA puede explorar más allá, pero la LISTA FINAL a sondear se fija con el usuario (regla 1b).
1b. **FIJAR MODELOS ANTES de sondear (17-ago-2026, fallo 2ª prueba).** Los ejemplos del briefing NO son la lista de sondeo directo. Antes de gastar peticiones, la skill presenta la lista de modelos que propone sondear y ESPERA el OK del usuario (añadir/quitar). El usuario manda qué modelos quiere ver. Fallo real: la skill sondeó 12 modelos del briefing y el usuario luego dio su lista propia (Ibiza, León, Cupra/Ateca, Arteon, Fiesta, Focus RS/ST, Golf altas, Polo 2016+), que era distinta y con restricciones (año ≥2016, acabados).
1c. **Criterio de selección POR CATEGORÍA (17-ago-2026).** NO aplicar el hueco de importación % a todos por igual:
  - 🔥 Showstoppers → criterio principal: ATRACTIVO/impacto visual y demanda (el hueco es secundario).
  - ⚙️ Alta rotación → criterio: hueco % + demanda masiva ES + fiabilidad.
  - 💎 Gemas → criterio: accesibilidad, coste de mantener/asegurar, durabilidad.
  Fallo real: Cupra (showstopper que el usuario SÍ quería) se descartó por "sin hueco" aplicando criterio de reventa — criterio equivocado para su categoría.
2. **Entregable = informe de búsqueda** (como Flujo C): Markdown + PDF de investigación con plantilla de marca (`assets/plantilla_pdf_marca.html`) + JSON. **NUNCA `.docx`** salvo pedido explícito.
3. **Listado-first (A17):** trabajar con LISTADOS, **NO abrir fichas**. Detalle solo 2-3 unidades como ejemplo (las de mejor precio relativo).
4. **Sellos de precio del listado (A17):** usar "Super precio"/"Buen precio" (Coches.net) y "Sehr guter/Guter/Fairer Preis" (mobile.de/AS24) para elegir muestras y entender el mercado sin abrir fichas. Selectores y escala en `../memoria/filtros-portales.md`.
5. **Precio verificado (A8):** DE = mobile.de, ES = Coches.net. **AS24 NUNCA para precio.**
6. **Datos de negocio por modelo** (12 campos de `../memoria/modelos-medidos.md`): nº anuncios, mediana DE/ES, hueco %, veredicto. NO basta "precio desde X €".
7. **Equipamiento NO inventado (A18):** solo lo verificado en la ficha real; lo dudoso "por confirmar".
8. **Checkpoint cada X (eficiencia):** en encargos grandes, entregar la PRIMERA categoría y **confirmar** antes de seguir con las demás. No soltar las 3 categorías de golpe sin validar el enfoque.

---

## 🎯 Las 3 categorías (briefing por defecto)

> ⚠️ **Los ejemplos son ILUSTRATIVOS (A19), no lista cerrada.** La IA explora TODO el segmento y añade modelos no nombrados por el usuario.

| Categoría | Perfil | Ejemplos (no exhaustivos) |
|---|---|---|
| 🔥 **Showstoppers** | Equipamiento muy alto, acabados deportivos, líneas exóticas | Golf 7 GTI/R · Cupra Leon/Ateca · Arteon SB · Astra OPC · A5 SB · Focus RS/ST · Serie 4 GC · (+ i30N, Megane RS…) |
| ⚙️ **Alta rotación** | Superventas fiables, mercado continuo ES, consumo/mantenimiento equilibrados | Golf VII (TDI/TSI R-Line) · León FR · Clase A/CLA AMG · A3 SB S-Line · Serie 1/3 M Sport |
| 💎 **Gemas económicas** | Accesibles, baratos de mantener/asegurar, buen primer coche | Fiesta ST-Line · Polo · Mini Cooper · Ibiza FR · Audi A1 · Mazda 3 |

---

## 🔀 Proceso del Flujo E (con Protocolo de Mando)

```
1. ENTENDER + FIJAR MODELOS (FASE 0 + PASO 3b del planificador):
   a. ACK de intención (búsqueda vs marketing) + entregable (informe, no anuncios)
   b. PASO 0 cache (reutilizar modelos ya medidos: Astra OPC, Golf GTI...)
   c. PROPUESTA de lista de modelos por categoría (criterio de la categoría, regla 1c) + encaje ES/DE
      → ESPERAR OK del usuario (añadir/quitar modelos) ANTES de sondear
2. PLAN DE FASE (3-5 líneas) con la lista YA fijada · OK del usuario → ejecutar
3. FASE 1 — LISTADOS (no fichas): por categoría, leer listados ordenados por precio en mobile.de (DE) + Coches.net (ES)
   → recoger por modelo: nº anuncios, precio-desde, mediana, hueco, 1-2 enlaces de ejemplo
4. ⏸️ CHECKPOINT tras la PRIMERA categoría: entregar el avance y CONFIRMAR el enfoque antes de seguir
5. ENTREGAR informe de STOCK (Markdown) con las categorías + anexo interno de enlaces
6. AUDITORÍA DE CIERRE (5 dimensiones) → registrar en encargos.md + modelos-medidos.md
```

**Presupuesto objetivo:** ~15-25 peticiones para 15 unidades (listados, no fichas). Abrir fichas duplica el coste sin aportar. **NO generar anuncios ni copy.**

---

## 📄 Plantilla de ficha de BÚSQUEDA (por modelo, NO anuncio)

```markdown
## [Marca Modelo · versión] — precio-desde [origen]
**Categoría:** 🔥/⚙️/💎 · **Origen:** DE/ES
- Nº anuncios DE: X · ES: X
- Mediana DE: X € · mediana ES: X €
- Hueco %: X% · sello de precio en listado: "Super precio"/"Buen precio"/"Sehr guter Preis"/...
- Precio-desde verificado (mobile.de/Coches.net): X €
- Veredicto: 🟢/🟡/🔴
- 1 enlace de ejemplo (anexo interno)
```

> **NUNCA** copy de Instagram/Facebook, hashtags, ni ficha marketplace en este flujo. Eso es marketing, otro encargo posterior.

---

## 📤 Output del Flujo E

| Archivo | Formato | Destino |
|---|---|---|
| `informe_busqueda_<fecha>.md` | Markdown (usuario) | `informes\stock\` |
| `informe_busqueda_<fecha>.pdf` | PDF de investigación (plantilla de marca `assets/plantilla_pdf_marca.html` → Chrome headless) | `informes\stock\` |
| `stock_<fecha>.json` | JSON (catálogo para Laravel) | `laravel\export\` |

**Es un INFORME DE BÚSQUEDA (Flujo C)**: misma estructura que `informe_busqueda_*.md` + PDF con el estilo de marca. **NUNCA .docx.** Si el usuario pide Word explícitamente, se convierte desde el Markdown, pero el entregable primario es Markdown + PDF de marca + JSON.

---

## 🔗 Referencias
- Reglas de negocio público/interno: `../SKILL.md` §NEGOCIO
- Precio de referencia: `../SKILL.md` §ARRANQUE (tabla de fuentes)
- Cache: `../SKILL.md` §PASO 0
- Costes de importación (si la ficha lo menciona): `../04-negocio/costes.md`
