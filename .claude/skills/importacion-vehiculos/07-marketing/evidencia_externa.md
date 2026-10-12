# 📚 EVIDENCIA EXTERNA — en qué se apoya cada regla del módulo

> No confundir con `fuentes_y_evidencia.md` (reglas de citación de fuentes de investigación).
> Este archivo documenta **de dónde sale cada regla de marketing importada de fuera**: portales, normativa y plataformas.

> Consultado el **06-sep-2026**. Cada regla del módulo que viene de fuera lleva aquí su fuente y su fecha.
> **Revisar cada 6 meses:** las reglas de portales y de algoritmos cambian. Si una fuente se contradice con lo que ves en el portal, manda el portal y se anota aquí.

---

## 1 · Portales

| Hallazgo | Regla que genera | Fuente |
|---|---|---|
| La descripción de Coches.net **PRO admite hasta 3.000 caracteres** | Diana de casa 1.200-1.800 (usar el espacio sin marear); tope duro 3.000 | [coches.net · blog profesionales, 5 consejos](https://www.coches.net/blog-profesionales/5-consejos-que-te-ayudaran-a-crear-anuncios-mas-eficaces-y-exitosos/) |
| **Incluir datos de contacto en la información adicional → el anuncio es rechazado** | Prohibido teléfono, email, WhatsApp o web en la descripción. El CTA remite al contacto del propio portal | [ayuda.coches.net · cómo debe ser la descripción](https://ayuda.coches.net/hc/es/articles/7487662867218--C%C3%B3mo-te-recomendamos-que-sea-la-descripci%C3%B3n-de-tu-anuncio-en-coches-net) |
| "Los signos de exclamación dificultan la lectura y hacen menos atractivo tu anuncio" | **Cero signos de exclamación** en portales | ídem |
| Campos obligatorios que deben ser correctos: marca, año, modelo, versión, km, color, precio | Todos los `[CAMPO]` rellenos, ninguno "otros" | ídem |
| "Incluye frases de búsqueda relevantes en el texto del anuncio"; 61 % de las búsquedas de VO en Google acaban en coches.net; 4 M de búsquedas/mes | El título y el primer párrafo llevan las palabras que la gente teclea (modelo + versión + acabado + cambio) | [coches.net · blog profesionales](https://www.coches.net/blog-profesionales/5-consejos-que-te-ayudaran-a-crear-anuncios-mas-eficaces-y-exitosos/) |
| Milanuncios permite **2 anuncios de coche gratis**; a partir de ahí deriva a Coches.net | No planificar campañas de más de 2 unidades simultáneas en Milanuncios | [ayuda.milanuncios.com · Motor](https://ayuda.milanuncios.com/hc/es/articles/360007480719-Motor) |

## 2 · Obligaciones legales (España)

| Hallazgo | Regla que genera | Fuente |
|---|---|---|
| Art. 20 TRLGDCU: la oferta comercial debe indicar **precio final completo, impuestos incluidos**, desglosando incrementos o descuentos, además de la **identidad del empresario** y las **características esenciales** del bien | El bloque de precio dice el importe final e impuestos y qué NO incluye; toda pieza pública lleva identificación de JJ Import Motors | [Art. 20 TRLGDCU](https://www.iberley.es/legislacion/articulo-20-ley-defensa-consumidores-usuarios) |
| "La carga de la prueba del cumplimiento de la información incumbirá al empresario" | Refuerza A26: cada dato publicado con su origen guardado en `[FUENTE_DATO]` | ídem |
| El vehículo de ocasión expuesto por un profesional debe mostrar marca, modelo, matrícula, si es propiedad del establecimiento, antigüedad, kilometraje, **fecha de primera matriculación**, servicio anterior, precio y garantía | **Ficha mínima** del anuncio (se añade fecha de 1ª matriculación y uso anterior cuando se conozcan) y, como somos **gestores** y no vendedores, se declara expresamente que el vehículo no es propiedad del establecimiento | [Comunidad de Madrid · coches de segunda mano y garantía](https://www.comunidad.madrid/servicios/consumo/coches-segunda-mano-compras-garantia) |
| Garantía legal en VO: 3 años, reducible por pacto a un mínimo de 1 año | **Nunca** escribir "garantía" sin decir cuál, quién la da y cuánto dura | ídem |

## 3 · Redes sociales

| Hallazgo | Regla que genera | Fuente |
|---|---|---|
| Adam Mosseri (jul-2026): *"Hashtags work, but they've never been a good way to actually increase your reach"*; su efecto es marginal y sirven para **categorizar**, no para alcanzar | Confirma el cupo de 5 y quita presión al hashtag: el trabajo lo hace el gancho | [Kontentino · Mosseri sobre hashtags](https://www.kontentino.com/q-and-a/instagram-hashtags-reach/) |
| La búsqueda de Instagram lee el **texto del caption**, no solo los hashtags | **Regla de la primera línea:** marca + modelo + versión aparecen en las dos primeras líneas, escritos como la gente los busca | [Playbook de sends per reach](https://influencermarketinghub.com/instagram-sends-per-reach-playbook/) |
| **Sends per reach** (envíos por DM ÷ alcance) es hoy una de las señales de ranking más fuertes: *"create something people want to send to a friend"*. Referencia: ~3-5 % es bueno | Nuevo bloque **`[IG_SEND_ASK]`**: una línea que nombra al destinatario concreto ("mándaselo a quien lleva medio año buscando uno"), nunca "comparte con 5 amigos" | ídem |
| Vídeo corto: ventana crítica **0-3 s**; duración con mejor tasa de finalización **9-15 s**; texto del gancho 6-10 palabras, CTA 2-3 palabras; **zona segura**: evitar los 120 px superiores e inferiores; caption de TikTok ≤100 caracteres; mínimo **2 variantes de gancho** en rotación | Guion reescrito a 12-15 s con 5 escenas cortas, rótulos con límite de palabras, aviso de zona segura y **dos ganchos obligatorios (A/B)** | [Buenas prácticas creativas TikTok](https://www.mbadv.agency/tiktok-ads/creative-best-practices) |

> ⚠️ Los números de vídeo vienen de práctica publicitaria (agencia), no de documentación oficial de TikTok: son **referencias de partida**, no dogma. Se validan con tus propios datos en `../memoria/marketing-resultados.md`.

## 3.1 · Entrega técnica (07-sep-2026)

| Hallazgo | Regla que genera | Fuente |
|---|---|---|
| WhatsApp muestra la previsualización con Open Graph: `og:image` de **1200×630**, **máx. 600 KB**, JPG/PNG/WebP (GIF y SVG no), URL absoluta HTTPS y **una sola** etiqueta; por debajo de 300 px la miniatura sale pequeña y por debajo de 100×100 no sale. La caché no se puede refrescar oficialmente (se añade `?v=2`) | Bloque de meta obligatorio en la ficha del cliente, con la primera foto real recortada a 1200×630 | [Guía técnica de previsualización en WhatsApp](https://www.ogrilla.com/blog/whatsapp-link-preview-guide) · [Especificaciones de imagen](https://opengraphplus.com/consumers/whatsapp/images) |
| Las animaciones dirigidas por scroll en CSS (`animation-timeline: view()`) son mejora progresiva; el nuevo **scroll-triggered** llega en Chrome 145, y el propio Chrome recomienda `IntersectionObserver` como fallback | Estrategia de tres capas: contenido visible por defecto → CSS nativo con `@supports` → fallback con IntersectionObserver, todo apagado con `prefers-reduced-motion` y en modo PDF | [Chrome for Developers · scroll-triggered animations](https://developer.chrome.com/blog/scroll-triggered-animations) |

## 4 · Método (skills de marketing del plugin)

Elementos adaptados de `marketing:brand-review` y `marketing:campaign-plan`: severidad Alto/Medio/Bajo en la revisión, bloque fijo de compliance, definición de voz en positivo ("suena así / no suena así"), tono por situación, jerarquía de mensaje con **proof point** por pilar, tabla de estilo mecánico, terminología preferida, variantes A/B por defecto y métricas por tipo de publicación.
