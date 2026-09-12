# 🔌 HANDOFF A LARAVEL — que lo que sale de aquí llegue intacto al panel

> **Problema que resuelve:** hasta ahora Claude entregaba `.txt` con bloques `[MARCADOR]` y Laravel los
> interpretaba. Cualquier ambigüedad (una barra de más, un bloque repetido, un bloque vacío) se convertía
> en una ficha mal pintada.
> **Regla nueva (07-sep-2026):** **manda el JSON**. El `.txt` sigue existiendo porque lo edita una persona,
> pero el ZIP lleva además `contenido/json/*.json` generado por `scripts/esqueleto_a_json.py`, y **Blade lee el JSON**.

---

## 1 · Qué viaja en el ZIP

```
[coche_id].zip
├── informe.json
├── manifest.json
├── contenido/
│   ├── ficha-cliente.txt        ← editable por humanos
│   ├── redes-sociales.txt
│   ├── anuncio-portales.txt
│   ├── informe-interno.txt · dossier-cliente.txt · ficha-publicitaria.txt
│   └── json/
│       ├── ficha-cliente.json   ← LO QUE CONSUME BLADE
│       ├── redes-sociales.json
│       └── anuncio-portales.json
└── fotos/
```

Generación (paso 5 del Flujo M, antes de cerrar el ZIP):

```bash
python scripts/check_marketing.py contenido/          # 0 hallazgos 🔴
python scripts/esqueleto_a_json.py contenido/ --out contenido/json/
```

Si el validador no está en verde, **no se genera el JSON ni el ZIP**.

---

## 2 · Forma del JSON de la ficha del cliente

```json
{
  "_meta": {"archivo":"ficha-cliente.txt","generado":"2026-09-07","version":2,"bloques_ausentes":[]},
  "tipo": "ficha_cliente",
  "coche_id": "...",
  "ficha": {
    "titulo": "Opel Astra OPC 2.0 Turbo",
    "subtitulo": "2015 · 62.400 km · gasolina · manual de 6 velocidades",
    "precio": "18.900 €",
    "precio_nota": "Precio final, impuestos incluidos...",
    "estado_proceso": "Disponible",
    "resumen_bueno": "...", "resumen_ojo": "...", "resumen_paso": "...",
    "spec": [{"etiqueta":"Kilómetros","valor":"62.400 km"}],
    "equipamiento": ["Asientos Recaro","Navegador"],
    "equipamiento_pendiente": ["Cámara trasera: por confirmar"],
    "verificado": ["..."], "pendiente_comprobar": ["..."],
    "fotos": ["01_frontal_34.jpg","02_lateral.jpg"],
    "argumentos": ["**280 CV con un solo dueño:** ..."],
    "mercado_min": 19900, "mercado_mediana": 22400, "mercado_max": 25500,
    "mercado_n": 8, "mercado_fecha": "07/09/2026", "mercado_nota": "...",
    "incluye": ["..."], "no_incluye": ["..."],
    "pasos": [{"cuando":"Semana 0","que":"Reserva y bloqueo de la unidad"}],
    "hacemos": ["..."], "no_hacemos": ["..."],
    "no_garantia": "JJ Import Motors no ofrece garantía...",
    "faq": [{"pregunta":"¿El coche es vuestro?","respuesta":"No..."}],
    "cta": "Quiero gestionar la compra", "contacto": "...",
    "aviso_legal": "...", "fecha_datos": "07/09/2026"
  },
  "_bloques": { "FC_TITULO": ["..."] }
}
```

`_bloques` es el crudo por si hace falta algo que el mapeo no cubre. `_meta.bloques_ausentes` dice qué falta: **si no está vacío, la ficha no se publica**.

---

## 3 · Mapa bloque → componente Blade

| Clave JSON | Tipo | Componente sugerido | Si falta |
|---|---|---|---|
| `titulo` `subtitulo` | string | `<x-ficha.hero>` | No se publica |
| `precio` `precio_nota` | string | dentro del hero | No se publica |
| `estado_proceso` | enum | píldora: Disponible/Reservado/En tránsito/Entregado | "Disponible" |
| `resumen_bueno/ojo/paso` | string | `<x-ficha.resumen>` (3 tarjetas ✅⚠️📍) | Se oculta la tarjeta que falte, nunca la sección |
| `spec[]` | `{etiqueta,valor}` | `<x-ficha.spec>` tabla 2 columnas | Sección oculta |
| `equipamiento[]` / `equipamiento_pendiente[]` | string[] | chips (los pendientes en ámbar) | Se ocultan |
| `verificado[]` / `pendiente_comprobar[]` | string[] | dos columnas ✅ / ☐ | **`pendiente_comprobar` vacío = error de contenido**, no se oculta en silencio |
| `fotos[]` | string[] | galería + lightbox | Sección oculta (mín. 5 para publicar) |
| `argumentos[]` | markdown ligero (`**negrita**`) | lista con ✓ | Sección oculta |
| `mercado_*` | número/string | barra de rango mín-mediana-máx + nota | Sección oculta si falta `mercado_n` o `mercado_fecha` |
| `incluye[]` / `no_incluye[]` | string[] | dos columnas ✓ / ✕ | No se publica |
| `pasos[]` | `{cuando,que}` | timeline horizontal | Sección oculta |
| `hacemos[]` `no_hacemos[]` `no_garantia` | string[] / string | bloque legal destacado | **No se publica** (A31) |
| `faq[]` | `{pregunta,respuesta}` | acordeón `<details>` | Sección oculta (mín. 6) |
| `cta` `contacto` | string | botón único + barra fija en móvil | No se publica |
| `aviso_legal` `fecha_datos` | string | pie | **No se publica** |

**Negrita:** el único marcado permitido es `**texto**` → `<strong>`. Se convierte con un helper, nunca con `{!! !!}` sobre contenido crudo (escapar primero, sustituir después).

---

## 4 · Reglas del parser (casos que rompen)

1. `[BLOQUE] valor` en la misma línea = valor de una línea. `[BLOQUE]` solo = todo lo que sigue hasta el próximo bloque.
2. Bloque repetido = **lista, en orden de aparición**. Nunca se sobrescribe el anterior.
3. Líneas que empiezan por `#` = comentario, se descartan.
4. Campos múltiples con ` | ` (espacio-barra-espacio). En `FC_FAQ` la respuesta puede contener barras: **solo se parte por la primera**.
5. Bloque vacío = no existe. Nunca se pinta una sección con contenido vacío.
6. Los importes vienen ya formateados en español (`18.900 €`); en el JSON los de mercado van además como número. **No reformatear en Blade.**
7. Todo el contenido se escapa por defecto (`{{ }}`); solo el helper de negrita produce HTML.

---

## 5 · Previsualización en WhatsApp (esto decide si abren el enlace)

El enlace se manda por WhatsApp, así que la tarjeta de previsualización es la primera impresión. Requisitos comprobados:

| Etiqueta | Valor |
|---|---|
| `og:title` | `Opel Astra OPC 2.0 Turbo · 2015 · 62.400 km` |
| `og:description` | Una frase: resumen_bueno recortado + precio |
| `og:image` | **1200×630 px**, JPG/PNG/WebP, **máx. 600 KB**, URL absoluta y HTTPS, **una sola** etiqueta |
| `og:url` `og:type` `og:site_name` | URL canónica del token · `website` · JJ Import Motors |

- Por debajo de 300 px de ancho WhatsApp pinta la miniatura pequeña; por debajo de 100×100 no pinta nada.
- **GIF y SVG no valen.**
- WhatsApp cachea la previsualización y no hay forma oficial de refrescarla: si cambia la foto principal, se añade `?v=2` a la URL al reenviarla.
- La imagen debe ser la **primera foto real del coche** recortada a 1200×630 (no el logo). Se puede generar en el momento del import con Intervention Image.

---

## 6 · Animaciones al hacer scroll

Sí, merecen la pena — pero con tres condiciones: que no bloqueen el contenido, que respeten a quien pide menos movimiento y que **se apaguen al generar el PDF**.

### Estrategia en tres capas

```css
/* 1 · Estado base: SIEMPRE visible. Si el JS o el CSS fallan, la ficha se lee igual. */
.reveal { opacity: 1; transform: none; }

/* 2 · Mejora progresiva con CSS nativo (Chrome, Edge y Safari recientes) */
@supports (animation-timeline: view()) {
  @media (prefers-reduced-motion: no-preference) {
    .reveal {
      animation: reveal-in both;
      animation-timeline: view();
      animation-range: entry 10% cover 30%;
    }
  }
}
@keyframes reveal-in { from { opacity:0; transform: translateY(18px) } to { opacity:1; transform:none } }

/* 3 · Fallback con IntersectionObserver para el resto (clase .js-reveal) */
```

```js
// Fallback: solo se activa si el navegador NO soporta scroll-driven y el usuario no pide menos movimiento
const soporta = CSS.supports('animation-timeline: view()');
const quietud = matchMedia('(prefers-reduced-motion: reduce)').matches;
if (!soporta && !quietud && 'IntersectionObserver' in window) {
  document.querySelectorAll('.reveal').forEach(el => el.classList.add('js-oculto'));
  const io = new IntersectionObserver((entradas) => {
    entradas.forEach(e => { if (e.isIntersecting) { e.target.classList.add('js-visible'); io.unobserve(e.target); } });
  }, { rootMargin: '0px 0px -10% 0px', threshold: 0.08 });
  document.querySelectorAll('.reveal').forEach(el => io.observe(el));
}
```

### Reglas

1. **Nada aparece por JavaScript.** El contenido está en el HTML y visible; la animación solo lo adorna.
2. **`prefers-reduced-motion: reduce` apaga todo.** Es accesibilidad, no una opción.
3. **Modo PDF:** Browsershot renderiza con Chrome headless. Se pasa `?pdf=1` (o una variable de vista) y el layout añade `<body class="modo-pdf">`, con `.modo-pdf .reveal{animation:none!important;opacity:1!important;transform:none!important}`. También `@media print` con lo mismo. Sin esto, el PDF sale con secciones a medio aparecer.
4. **Nada de animar el hero.** El primer bloque se pinta completo: es el LCP y es lo que se ve en la miniatura.
5. **Solo `opacity` y `transform`.** Nunca animar `height`, `top` o `width`: provocan reflow y van a tirones en móvil.
6. **Contadores** (precio, km, mediana): suben desde 0 una sola vez al entrar en pantalla, en ≤ 700 ms, y **el valor final está en el HTML** por si no corre el JS.
7. **Cero librerías por defecto.** Con CSS + IntersectionObserver sobra para esta página. Si algún día se quiere un efecto mayor:

| Necesidad | Opción | Peso aproximado | Cuándo |
|---|---|---|---|
| Revelados y contadores | CSS + IntersectionObserver | 0 KB | **Por defecto** |
| Secuencias encadenadas | Motion One | ~5 KB | Si el diseño lo pide |
| Scroll suave con inercia | Lenis | ~3 KB | Solo si gusta el efecto; ojo en móvil |
| Timelines complejos | GSAP + ScrollTrigger | ~50 KB | Difícil de justificar aquí |

Todo lo que se añada va con `defer` y **fuera** del render inicial.

---

## 7 · Rendimiento e imágenes

- Las fotos del coche pesan: servir **WebP** con `srcset` (480/960/1440) y `loading="lazy"` en todas menos la primera, que va `fetchpriority="high"`.
- `width` y `height` (o `aspect-ratio`) en todas: evita el salto de layout mientras cargan.
- Galería con lightbox nativo (`<dialog>`), sin librería.
- Objetivo: que la ficha abra en móvil con 4G en menos de 2,5 s hasta ver título, precio y primera foto.

---

## 8 · Accesibilidad mínima

- Contraste AA en texto sobre el azul de marca y sobre el ámbar del aviso.
- El acordeón de FAQ con `<details>/<summary>` nativo (funciona con teclado sin código).
- Foco visible en el botón de CTA y en los enlaces de contacto.
- La galería con `alt` real ("frontal tres cuartos", no "foto 1").
- La barra fija de CTA en móvil no puede tapar el aviso legal: `padding-bottom` en el `body` equivalente a su altura.

---

## 9 · Checklist de aceptación (cuándo está bien integrado)

- [ ] El Blade lee `contenido/json/ficha-cliente.json`, no el `.txt`
- [ ] `_meta.bloques_ausentes` vacío antes de publicar
- [ ] Las 14 secciones pintan con el ejemplo de `07-marketing/plantillas/ejemplo/ficha-cliente.txt`
- [ ] Ninguna sección aparece vacía ni con "Array"
- [ ] `**negrita**` se convierte y el resto del contenido va escapado
- [ ] Bloque legal y aviso de pie siempre visibles (A31)
- [ ] Ni vendibilidad, ni número de competidores, ni ahorro estimado en ninguna parte
- [ ] Etiquetas Open Graph correctas y previsualización probada en un WhatsApp real
- [ ] Con `prefers-reduced-motion: reduce` no se mueve nada
- [ ] `?pdf=1` genera el PDF sin animaciones y sin la barra fija de CTA
- [ ] En móvil de 360 px no hay scroll horizontal

Referencia visual: `informes\marketing\mockup_ficha_cliente_v2.html` (versión con animaciones incluidas).

---

## 11 · Panel admin `/cars/{id}` troceado (12-sep-2026)

El panel admin interno (`/cars/{id}`) está dividido en **partials Vue independientes** para que la skill NO
toque nada del panel: solo entrega el ZIP y Laravel lo pinta con sus propios componentes.

### Estructura

```
resources/js/Pages/Cars/
├── Show.vue                          (717 líneas — solo orquesta imports + layout)
├── Partials/
│   ├── HeaderBar.vue                 cabecera: estado, badge origen, acciones
│   ├── OverviewPanel.vue             ficha técnica + spec[]
│   ├── InvestigationPanel.vue        bloques [MARCADOR] / zip
│   ├── MarketPanel.vue               mercado_min / mediana / max
│   ├── ChecklistPanel.vue            verificado[] / pendiente_comprobar[]
│   ├── AssignRequestPanel.vue        asignación cliente ↔ solicitud
│   ├── NotesPanel.vue                notas internas
│   ├── ExpensesPanel.vue             gastos (lee PrecioClienteCalculator)
│   └── PhotosPanel.vue               galería + orden
└── Modals/
    └── ShareTrackingModal.vue        modal único de compartir tracking
```

### Contrato con la skill

- La skill entrega ZIP → `ValuationPackageIngestor` lo ingesta → `ContenidoCocheViewModel` lo proyecta.
- **Show.vue solo recibe props de Inertia** y las pasa al partial correspondiente.
- Cada partial tiene su propio `defineProps({ coche: Object })` y se renderiza con v-if según pestañas.

### Caption canónico del precio (v3.9.2+)

> "Precio del vehículo + gastos gestión de compra" — NUNCA volver a "gastos de gestión de compra e importación".

### Regla operativa para la skill

**No tocar nada del panel admin.** Si la skill necesita exponer un dato nuevo:
1. Lo añade al JSON de `ficha-cliente.json` (o crea una clave nueva).
2. Lo declara en el `manifest.json`.
3. Avisa al equipo Laravel para que monte el partial (o lo extienda).

La skill NO edita `.vue` directamente porque las dos cosas divergirían otra vez.

---

## 🔗 Referencias
- Contenido de la ficha: `ficha_cliente.md` · Reglas comunes: `copy_engine.md`
- Fuentes y fechas: `fuentes_y_evidencia.md`
- Conversor: `../scripts/esqueleto_a_json.py` · Validador: `../scripts/check_marketing.py`

---

## 10 · Estado real de la cadena (09-sep-2026)

Circuito cerrado y probado: **`empaquetar.py` → ZIP → ingestor → Blade**.

| Pieza | Qué hace ahora |
|---|---|
| `scripts/empaquetar.py` | Genera **6 esqueletos** (añade `ficha-cliente.txt`, solo con veredicto Comprar*) y escribe `contenido/json/*.json` llamando a `esqueleto_a_json.py`. Los declara en el `manifest.json`. |
| Bloques de marketing | Emite **los dos vocabularios**: los `TIKTOK_/INSTAGRAM_/FACEBOOK_POST_n` que lee hoy `ValuationPackageIngestor` **y** los `IG_/VT_/FB_/FBMP_/PT_` que exige `check_marketing.py`. Puente consciente: nada se rompe mientras el panel migra al v2. |
| `ValuationPackageIngestor` | Acepta `.txt` **y `.json`** dentro de `contenido/`. |
| `PublicCarController` | Busca la ficha en `contenido/json/ficha-cliente.json` y, si el ingestor aplanó el nombre, en `contenido/ficha-cliente.json`. |
| Parsers | `check_marketing.py` y `esqueleto_a_json.py` interpretan el formato **igual que `App\Support\Esqueleto`**: `[BLOQUE] valor` admite continuación en las líneas siguientes y un nombre repetido es una lista. Antes el validador solo aceptaba el marcador en línea propia: por eso daba «archivo sin bloques reconocibles» con todo lo que generaba `empaquetar.py`. |

**Prueba de humo (repetible):**

```bash
python scripts/empaquetar.py scripts/fixtures/flujo-a-bmw-320d-2020-test.json --out /tmp/paq --no-photos
cd /tmp/paq && unzip -o bmw-320d-2020-test.zip -d x
python scripts/check_marketing.py x/contenido/redes-sociales.txt x/contenido/anuncio-portales.txt
python scripts/check_ficha_cliente.py x/contenido/ficha-cliente.txt
```

**Deuda consciente:** el panel sigue leyendo el vocabulario v1.5 para crear `CarMarketingContent`. Cuando migre a `IG_/VT_/FB_/FBMP_`, se pueden retirar los bloques antiguos de `generar_redes_sociales` y quedarse solo con `_bloques_v2_redes`.

