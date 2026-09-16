---
name: ecommerce-tuning
version: 0.1.0
description: >
  E-commerce de accesorios y tuning de coches (España). Modelo TRAMITADOR PURO:
  sin almacén y sin stock — dropshipping con proveedores China/UE; nunca se
  compra lote. Cuatro flujos: S SEÑALES (TikTok Creative Center + Meta Ads
  Library + Google Trends), P PRODUCTO (búsqueda inversa proveedor
  1688/Alibaba/CJ + filtro de viabilidad financiera), F FICHA (ficha SEO +
  borrador Shopify), M MARKETING (copy multicanal Meta/TikTok/IG/Google
  Shopping). Navegación real: navegador interno de Claude por defecto; Claude
  para Chrome cuando haga falta login (1688, Alibaba, CJ) o el sitio sea JS
  pesado/anti-bot. Aprobación humana SIEMPRE antes de publicar o gastar.
  Guía maestra del negocio: docs/ecommerce.md del repo ImportnexCore.
triggers:
  - escanea tendencias tuning
  - tendencias de la tienda
  - busca productos para la tienda
  - productos que merecen la pena
  - evalúa este producto
  - evalua este producto viral
  - busca proveedor de
  - merece la pena este producto
  - lanza producto
  - lanzamiento completo de
  - ficha de producto
  - anuncios para la tienda
  - copy para el producto
  - tiktok creative center
  - meta ads library
---

# E-commerce tuning — tienda tramitadora (sin almacén)

Tienda online de accesorios y tuning de coches vendiendo en España con proveedores chinos y europeos. **Modelo tramitador puro**: vendes y tramitas; el proveedor envía. Nunca se compra stock.

> ⚠️ **NO confundir con JJ Import Motors** (importación de coches, skill `importacion-vehiculos`). Negocio distinto: otro producto, otros costes, otra memoria. Nada se comparte salvo el método de navegación real.

## 🔴 REGLAS DURAS (E1-E12)

- **E1 · SIN STOCK.** Nunca planificar compra de lote. Producto que despega (10+ ventas/mes) → migrar a proveedor con almacén UE y subir puja en ads. Prohibido sugerir "pedir 20-50 unidades".
- **E2 · APROBACIÓN HUMANA.** Nada se publica, ningún euro en ads, ningún mensaje a proveedor con intención de pedido, sin "APRUEBO" explícito del usuario. El agente entrega borradores y alertas.
- **E3 · NAVEGACIÓN REAL.** Estilo humano: clic, espera, screenshot antes de concluir. Si una página no renderiza, degradar (versión móvil, otra fuente) y anotar — no insistir en bucle.
- **E4 · NAVEGADOR.** Interno de Claude por defecto. Cambiar a Claude para Chrome solo si: login requerido (1688, Alibaba, CJ), JS pesado que el interno no renderice, o anti-bot (TikTok a veces). El Chrome del usuario tiene sus sesiones: no cerrarlas ni hacer logout de nada.
- **E5 · FILTRO OBLIGATORIO.** Todo candidato pasa el filtro de viabilidad (§ El filtro) antes de ficha o marketing. Sin excepciones "porque está de moda".
- **E6 · COSTES COMPLETOS.** El margen SIEMPRE con: arancel + IVA importación + gestión aduanera (si envío desde China) + comisión pasarela + CPA estimado + provisión devoluciones. Prohibido "precio venta − precio AliExpress".
- **E7 · LEGALIDAD.** Nunca vender bombillas LED de faro como legales; piezas de rendimiento llevan aviso de homologación; prioridad a categorías Nivel 1 (interior, estética, iluminación ambiental, short shifter, accesorios). Ver docs/ecommerce.md §4.
- **E8 · IMÁGENES.** No limpiar marcas de agua ni reutilizar fotos de terceros sin derecho. Fotos propias de muestra > fotos de catálogo. Pedir fotos reales al proveedor.
- **E9 · MEMORIA PRIMERO.** Consultar `memoria/` ANTES de navegar. Todo producto evaluado (aunque se rechace) se registra. No re-investigar dos veces lo mismo.
- **E10 · SIN SCRAPING MASIVO.** Nada de enumeración agresiva ni automatización fuera del navegador de Claude. Ritmo humano.
- **E11 · FUENTES CITADAS.** Todo entregable lleva enlaces: anuncio viral, página de proveedor, Trends, competidores. Dato sin fuente = no entregado.
- **E12 · SIN PROMESAS FALSAS.** Ni plazos que el proveedor no cumple, ni precio tachado inventado, ni "legal para circulación" en piezas que no lo son.

## ⚡ REFERENCIA RÁPIDA

```
Modelo: tramitador puro — 0 stock, 0 almacén, 0 logística propia
Umbrales: Score ≥ 7,5 · margen bruto ≥ 40% · ratio ≥ 3× sobre coste producto+envío
Restricciones: peso >1kg, baterías litio, líquidos → FUERA
Categorías: Nivel 1 primero (interior, estética, iluminación ambiental, short shifter)
Precio venta objetivo: 30-150€
Señales: TikTok Creative Center · Meta Ads Library · Google Trends · Amazon.es
Proveedores: 1688 (fábrica) · Alibaba (Verified Manufacturer + Trade Assurance) · CJ/Zendrop UE (rapidez)
Filtro completo: docs/ecommerce.md §14.2 — single source of truth
CPA estimado: Meta 15-25€ · TikTok 8-15€ · Google Shopping 10-18€ (usar cota alta si no hay datos)
Checkpoint: parada de aprobación entre P→F→M (E2)
Memoria: consultar ANTES de navegar (E9)
```

## 🧮 El filtro (viabilidad financiera)

Con TODOS los costes (E6):

```
Margen Neto = P_venta − (C_producto + C_envío + [Arancel + IVA import + Gestión aduanera]
              + Comisión pasarela (~2% + 0,25€) + CPA_est + Provisión devoluciones (5%))

Score = (Engagement Rate × Margen Neto %) / (Anuncios competidores activos + 1)
```

Aprobado solo si **las 3** se cumplen:

1. `Score ≥ 7,5`
2. `Ratio = P_venta / (C_producto + C_envío) ≥ 3×`
3. Sin restricciones logísticas (peso >1kg, baterías, líquidos)

**Matiz tramitador:** proveedor con almacén UE con margen bruto ≥40% gana el desempate aunque el ratio sea menor que el chino — menos plazo (3-7 días vs 15-40), menos devolución, menos soporte.

**De dónde sale cada dato:**

| Dato | Fuente |
|---|---|
| Engagement Rate | TikTok Creative Center (likes+comentarios+shares / vistas del anuncio viral) |
| P_venta (techo de mercado) | Amazon.es + eBay.es + tiendas ES de la competencia |
| C_producto + C_envío | 1688/Alibaba/CJ (precio 1 ud dropshipping blind) |
| Anuncios competidores activos | Meta Ads Library (búsqueda del producto, España) |
| Demanda (aceleración) | Google Trends España 12 meses |
| CPA_est | Tabla referencia rápida; cota alta si no hay histórico propio |

## 🎯 LOS 4 FLUJOS

| Flujo | Disparador | Qué hace | Output |
|---|---|---|---|
| **S: SEÑALES** | "escanea tendencias", "qué productos merecen la pena" | Rastrea señales de mercado, shortlist 5-10 candidatos | `informe-senales.md` |
| **P: PRODUCTO** | "evalúa este producto [enlace]", "busca proveedor de X" | Búsqueda inversa proveedor + filtro completo + veredicto | `informe-viabilidad-<slug>.md` |
| **F: FICHA** | "ficha de X", tras P aprobado | Ficha SEO completa lista para Shopify (borrador) | `ficha-<slug>.md` |
| **M: MARKETING** | "anuncios para X", tras F aprobado | Copy multicanal listo a pegar | `marketing-<slug>.md` |

Cascada con checkpoints (E2): S → (usuario elige candidatos) → P → (APRUEBO) → F → (APRUEBO) → M. Nunca se entregan todos de golpe sin parada de aprobación.

### Flujo S · SEÑALES

1. Memoria primero (E9): descartar productos ya evaluados.
2. TikTok Creative Center → anuncios automoción España, últimos 7 días, orden por crecimiento de interacción.
3. Meta Ads Library → producto con anuncios activos desde hace meses = validado por la competencia.
4. Google Trends ES 12m → término en aceleración (no declive).
5. Amazon.es/eBay.es → reseñas y precios techo.
6. Shortlist 5-10 con: producto, señal detectada, métricas, enlaces (E11), primera estimación de hueco.

### Flujo P · PRODUCTO

1. Memoria primero.
2. Búsqueda inversa por imagen del candidato en 1688 → fabricante primario. Si no, Alibaba (Verified Manufacturer, Trade Assurance, 5+ años) → CJ/Zendrop UE.
3. Del proveedor: precio 1/10/50 uds, MOQ, dropshipping blind (sin factura/publicidad), certificados CE/E-mark, años, tasa devoluciones, on-time, fotos reales.
4. P_venta de mercado (Amazon + competencia ES).
5. Aplicar el filtro completo (§ El filtro). Veredicto: ✅ APRUEBA / ❌ RECHAZA / ⚠️ DUDA (falta dato).
6. Registrar en `memoria/productos-evaluados.md` (también los rechazados) y proveedor en `memoria/proveedores.md` si es nuevo.

### Flujo F · FICHA

Estructura (docs/ecommerce.md §9):

- Título: `[Producto] [Marca/Modelo compatible] [Característica clave]`
- Descripción: frase de beneficio → compatibilidad (con ⚠️ verifica motorización) → qué incluye → especificaciones → instalación (dificultad, tiempo, herramientas) → envío y garantía → aviso homologación si Nivel 2
- Fotos: 5-6 (propias de muestra si existen; si no, reales del proveedor con permiso — E8)
- Precio terminado en 9 o 5; envío gratis desde X€ en vez de descuento
- Estado: BORRADOR en Shopify. Nunca publicado (E2).

### Flujo M · MARKETING

- Meta Ads: 3 variantes (ángulo beneficio / prueba social / oferta) — headline ≤40 chars, primary text 90-125 palabras, CTA
- TikTok: guion 15-25s con gancho en 0-3s, texto en pantalla, sonido trending
- IG: caption con hashtags de modelo (#golfmk7 etc.), 3-5 máximo
- Google Shopping: título ≤150 chars con compatibilidad
- Todo sujeto a E12 (sin promesas falsas) y a los umbrales de decisión de ads (docs/ecommerce.md §10)

## 📦 Entregables

Carpeta por sesión: `informes-ecommerce/YYYY-MM-DD-<slug>/` con los ficheros del flujo correspondiente. Nombres: `informe-senales.md`, `informe-viabilidad-<slug>.md`, `ficha-<slug>.md`, `marketing-<slug>.md`.

## 🧠 Memoria

- `memoria/MEMORIA.md` — índice y decisiones
- `memoria/productos-evaluados.md` — TODO candidato evaluado con veredicto y fecha
- `memoria/proveedores.md` — proveedores verificados y descartados
- `memoria/trampas.md` — fuentes que fallan, anti-bots, proveedores fantasma

Regla: rellenar SIEMPRE al cerrar cada flujo (E9). Un producto rechazado hoy puede reaparecer viral mañana — la memoria evita re-hacer el trabajo y detecta cambios de precio.

## 🚫 Anti-patrones rápidos

- Margen sin aranceles/pasarela/CPA/devoluciones → recalcular (E6)
- Aprobar "porque está de moda" sin filtro → prohibido (E5)
- Sugerir comprar lote/stock → viola el modelo (E1)
- Publicar/lanzar ads sin APRUEBO → prohibido (E2)
- Fotos ajenas con marca de agua limpia → prohibido (E8)
- Entregar sin enlaces a fuentes → incompleto (E11)
- Re-evaluar sin mirar memoria → desperdicio (E9)
- Insistir contra anti-bot en bucle → degradar y anotar (E3, E10)
- Mezclar criterios/costes con la importación de coches → negocios distintos (cabecera)
