# Trampas encontradas — Catálogo

> Trampas y patrones raros detectados en los portales. Cuando aparece 3 veces la misma trampa, se sistematiza en `../02-flujos/extractores.md`.

---

## 🚨 Trampas confirmadas (3+ apariciones)

### 🚩 AutoUncle redirige si el modelo está mal escrito
**Portal:** AutoUncle
**Síntoma:** Redirige a modelo similar (ej: "Golf I" en vez de "Golf GTI")
**Detección:** Comprobar que la URL final tiene el modelo correcto
**Mitigación:** Validar URL tras redirección. Si redirige, ajustar slug y reintentar.

### 🚩 Wallapop `/app/search` está deprecated
**Portal:** Wallapop
**Síntoma:** `/app/search` no funciona / redirige a `/search`
**URL correcta:** `/search?q=<query>`
**Mitigación:** Usar siempre `/search` directamente.

### 🚩 Coches.net `/segunda-mano/coches/<marca>-<modelo>` vs `/marca-modelo-segunda-mano/`
**Portal:** Coches.net
**Síntoma:** La URL antigua redirige a `/noticias/`. La nueva es `/segunda-mano/coches/...`
**URL correcta:** `/segunda-mano/coches/<marca>-<modelo>`
**Mitigación:** No usar `/marca-modelo-segunda-mano/`

### 🚩 AutoScout24.de usa slugs con versión: `va_<version>`
**Portal:** AutoScout24.de
**Síntoma:** `golf-gti` redirige a una versión incorrecta
**URL correcta:** `golf-gti-va_<version>-ds` o similar
**Mitigación:** Comprobar el slug en la URL final tras la búsqueda.

### 🚩 mobile.de: filtro por variante de texto se pierde OPC genuinos (CRÍTICO · 12-ago-2026)
**Portal:** mobile.de
**Síntoma real:** Filtré por variante `OPC` (`ms=...;OPC`) y salieron 72 anuncios, pero SOLO 2 eran OPC genuinos. Un OPC real (8.999 €, EZ 10/2012, 106.000 km, 206 kW) **no apareció** porque su título era genérico "Opel Astra" sin la etiqueta OPC en los metadatos, aunque sus specs reales eran las del OPC.
**Causa raíz:** El campo "variante" es texto libre rellenado por cada vendedor. No es fiable. La potencia (kW/CV) viene del permiso de circulación → es un campo estructurado casi siempre correcto.
**Detección:** Comparar resultados del filtro por variante vs búsqueda libre por potencia.
**Mitigación OBLIGATORIA (doble pasada):**
1. **Búsqueda 1:** por variante de texto (`OPC`, `GTI`, `M`, `RS`...)
2. **Búsqueda 2:** por modelo base + filtro de **potencia** (kW) con margen ±10 kW sobre la del tope de gama + año ≥ + km ≤
3. **Cruce:** unión de ambas listas por ID de anuncio (no intersección). Los que aparecen en la 2 pero no en la 1 son los chollos escondidos.
**Aplicación:** idéntica en Coches.net (filtrar por CV), AS24 y resto de portales.

### 🚩 AutoScout24.de NO es fiable para precio de referencia (CRÍTICO · 12-ago-2026)
**Portal:** AutoScout24.de
**Síntoma real:** Se usó AS24 como referencia de precio para Ford Focus/Peugeot 308/Renault Mégane. Al cruzar con mobile.de filtrado, los "precios mínimos" de AS24 eran engañosos: anuncio siniestrado a 2.499 € y fechas mal etiquetadas colados en el listado barato.
**Causa raíz:** AS24 agrega feeds de varios portales SIN cribarlos (no valida siniestros, años, ni cv).
**Mitigación OBLIGATORIA:**
- **AS24 SOLO para contar** ofertas (N uds), NUNCA para precio.
- Precio DE de referencia = **mobile.de** (filtrado correctamente: siniestros fuera, año/cv correctos).
- Precio ES de referencia = **Coches.net**.
- Antes de recomendar un candidato con "precio desde", cruzar SIEMPRE con mobile.de/Coches.net.

### 🚩 Banda de precio en ambos mercados aplasta el hueco (CRÍTICO · 18-ago-2026)
**Portal:** todos
**Síntoma real:** La v1/v2 del estudio concluyó "Seat/Cupra → España más barata, nunca importar" usando mediana CON banda ≥20k en ambos mercados. El usuario lo refutó con datos: **Cupra León más barato ES = 19.500 €, más barato DE = 15-16.000 €**. La banda ≥20k recortó la cola barata alemana (igual que el error del Golf de la v2), y la mediana resultante daba un hueco falso.
**Causa raíz:** Aplicar la misma banda absoluta a ES y DE recorta simultáneamente la mitad cara del mercado caro y la barata del barato → la mediana no mide el hueco real (mide el techo del filtro).
**Mitigación OBLIGATORIA:**
1. **El hueco y el precio_desde (suelo) se miran SIN banda** en ambos mercados. La banda solo sirve para hablar del presupuesto con el cliente.
2. **La nacionalidad de la marca NO es criterio de arbitraje.** Seat/Cupra se fabrican en Martorell pero el mercado DE sigue teniendo unidades más baratas (mayor parque/rotación). No asumir "España más barata" por ser marca nacional.
3. Antes de declarar "ES más barato que DE", comparar SIEMPRE el **precio_desde sin banda** de ambos lados, no solo la mediana con filtro.
4. Regla metodológica de la v2 sigue vigente: control de año en ambos mercados + hueco sin banda.

---

## ⚠️ Trampas potenciales (1-2 apariciones)

### ⚠️ Mobile.de: filtro "Beschädigte Fahrzeuge" oculto
**Portal:** mobile.de
**Síntoma:** Por defecto NO muestra coches con daños. Hay que activarlo explícitamente.

### ⚠️ Milanuncios: listado virtualizado — solo monta la 1ª tarjeta (15-ago-2026) ✅ RESUELTO
**Portal:** Milanuncios
**Síntoma real:** Con scroll infinito solo renderiza la primera tarjeta (la patrocinada/"Destacado") aunque se haga scroll real repetido y se ordene por "más baratos primero". La pestaña se bloquea (timeout) antes de cargar el resto.
**Causa raíz:** Virtualización agresiva: el DOM solo monta las tarjetas del viewport; el scroll infinito no dispara la carga.
**✅ Resuelto (15-ago-2026):** la **paginación por URL** carga el listado completo y **respeta los filtros**. El contenedor es `.ma-AdList` y el parámetro es `&pagina=N` (confirmado navegando).
**Mitigación OBLIGATORIA (en orden):**
1. **Paginación por URL** (la fiable): `?s=<marca>+<modelo>&...&pagina=1`, luego `pagina=2`, `pagina=3`... — NO scroll infinito.
2. **Bandas de precio** (`&hasta=X&desde=Y`) para reducir el nº de resultados y agilizar.
3. Si aún así no carga → declarar `bloqueada (virtualización, N intentos)` en la cobertura y continuar (A7); el informe queda PARCIAL declarado.
**Mitigación:** Activar `dam=true` en la URL o filtro manual.

### ⚠️ Kleinanzeigen: "VB" = negociable
**Portal:** kleinanzeigen.de
**Síntoma:** El precio publicado tiene "VB" (Verhandlungsbasis) → no es el real
**Mitigación:** Restar 10-15% en negociaciones. Marcar como negociable.

### ⚠️ Coches.net: contador global vs por modelo
**Portal:** Coches.net
**Síntoma:** El H1 "259.347 coches" es global, no del modelo
**Mitigación:** Buscar el contador específico (ej: "8.991 resultados").

### ⚠️ km77: 503/504 frecuentes
**Portal:** km77.com
**Síntoma:** Backend caído (Cloudflare)
**Mitigación:** Fallback a BOE para PVP/CO₂. Marcar `km77_fallback: BOE`.

---

## 💡 Señales de chollo detectadas

### Señal 1: Días publicado >60
**Portal:** AutoUncle, AS24
**Por qué:** Indica que el vendedor está dispuesto a negociar.
**Combinada con:** Precio >Q1 + etiqueta buen precio = CONTACTAR.

### Señal 2: Cambio de precio reciente
**Portal:** AutoUncle (%), kleinanzeigen (€ anterior)
**Por qué:** El vendedor ha bajado el precio → motivable.
**Acción:** Ofrecer -5% sobre el nuevo precio.

### Señal 3: "VB" + Privatanbieter
**Portal:** kleinanzeigen
**Por qué:** Particular dispuesto a negociar (no profesional con margen)
**Acción:** Oferta directa al -10%/-15%.

### Señal 4: 2. Hand + TÜV NEU
**Portal:** mobile.de
**Por qué:** Coche veterano pero bien mantenido. Margen alto.
**Acción:** Verificar km por año y revisar comparables del mismo año.

---

## 📋 Plantilla para nueva trampa

```markdown
### [Título corto]
**Portal:** [portal]
**Síntoma:** [qué pasa]
**URL correcta:** [URL que funciona]
**Mitigación:** [cómo evitarlo]
```

---

## 🗓️ Última actualización

- **2026-08-12:** 4 trampas confirmadas, 4 potenciales, 4 señales de chollo.
