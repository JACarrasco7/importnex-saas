# ENTREGABLES — tabla única: qué informe, cuándo, cómo se llama y dónde va

> **Este fichero es la ÚNICA fuente de verdad de los entregables.** Si `SKILL.md`,
> `operaciones.md`, `stock-marketing.md` o `contrato.md` dicen otro nombre de fichero, otra
> carpeta u otro formato, **manda esta tabla** (y ese documento hay que corregirlo).
>
> **Cargar:** al detectar el flujo (PASO 1), al nombrar un fichero y antes de entregar.

---

## 1 · ¿Qué informe toca? (decisión por lo que dice el usuario)

| El usuario dice… | Flujo | Entregable | Plantilla |
|---|:---:|---|---|
| «evalúa este: <URL>» · «mira este coche» | **A** | Informe de **UNIDAD** + dossier + ZIP | `03-informes/informe_tecnico.md` |
| «busca [marca modelo]» · «encargo de un X» | **B** Fase 1 | **Informe de BÚSQUEDA** (un modelo) + Top 5 | `03-informes/informe_busqueda.md` |
| «qué merece la pena» · «escanea el mercado» · «revisa este segmento/familia» | **C** | **Informe de BÚSQUEDA** (N modelos) | `03-informes/informe_busqueda.md` |
| «cliente sin modelo, tiene X €» | **D** | **Informe de MODELOS** (país × año × motor) | `SKILL.md` §D2 |
| «stock recurrente» · «catálogo bajo pedido» · «busca por categorías para ofertar» | **E** | **Informe de BÚSQUEDA** (por categorías) | `03-informes/informe_busqueda.md` |
| «dame los anuncios» · «copy para redes» · «ficha de publicación» | **M** | `redes-sociales.txt` + `anuncio-portales.txt` | `07-marketing/` |
| «estudia el mercado de <modelo>» | estudio-mercado | **Informe de MERCADO** (estudio) | `estudio-mercado/informe_mercado.md` |
| «compáralos» (varios candidatos) | A/B | **COMPARATIVA** de candidatos | `SKILL.md` §comparativa |

> ⚠️ **Los dos que más se confunden:**
> - **Informe de BÚSQUEDA** = mercado, modelos, hueco, ofertas. *"¿Dónde está la oportunidad?"*
> - **Informe de UNIDAD** = un coche concreto, 15 secciones, score. *"¿Este coche vale lo que piden?"*
> - **Informe de MODELOS** (D) = qué modelos caben en un presupuesto, **sin anuncios ni enlaces**.

### 🛑 Si no encaja en ninguna fila, o caben dos → PREGUNTAR (no improvisar)

Una sola pregunta, corta, en la misma línea que el plan:

```
No lo tengo claro del todo. ¿Qué quieres que te prepare?
  a) Dónde está la oportunidad en el mercado (modelos, oferta, hueco) → informe de búsqueda
  b) Analizar este coche concreto a fondo → informe de unidad
  c) Los anuncios/copy para publicarlo → piezas de marketing
```

Y si en un mismo mensaje hay **dos verbos** de la tabla (ej. *"dime qué hay y hazme el anuncio"*):
es una **colisión** → se parte en 2 fases con checkpoint, nunca en el mismo entregable
(detalle en `01-arranque/guia_prompts.md` §Colisiones).

---

## 2 · Nombres de fichero (canónicos)

Regla de nombres: **minúsculas, sin tildes, sin espacios** (guiones), fecha **`YYYY-MM-DD`**.

| Entregable | Nombre canónico |
|---|---|
| Informe de búsqueda (B) | `informe_busqueda_<marca>-<modelo>_<YYYY-MM-DD>.md` |
| Informe de búsqueda (C/E) | `informe_busqueda_<segmento-o-categoria>_<YYYY-MM-DD>.md` |
| Informe de unidad (A) | `informe_unidad_<marca>-<modelo>_<YYYY-MM-DD>.md` |
| Informe de modelos (D) | `informe_modelos_<cliente-o-segmento>_<YYYY-MM-DD>.md` |
| Informe de mercado (estudio) | `informe_mercado_<marca>-<modelo>_<YYYY-MM-DD>.md` |
| Comparativa de candidatos | `comparativa_<YYYY-MM-DD>.md` |
| **PDF del informe de búsqueda/mercado (v3.10.0)** | **misma base que el .md con extensión `.pdf`** — generado SIEMPRE con `scripts/mercado_pdf.py <informe>.md`; es el entregable que lee el operador (el .md queda como contrato interno) |

**JSON (contrato con Laravel):**

| Contrato | Nombre canónico |
|---|---|
| Flujo A | `informe.json` **dentro del ZIP** |
| Flujo B | `export/flujo-b-<modelo>-<YYYY-MM-DD>.json` |
| Flujo C | `export/flujo-c-<YYYY-MM-DD>.json` (alimenta la tabla `scouting_mercado`) |
| Flujo E | `export/stock-<YYYY-MM-DD>.json` |

> ❌ Nombres **retirados** (no usar): `informe_busqueda_<fecha>.md` sin objeto,
> `informe_unidad_<fecha>.md` sin modelo, `scouting_<fecha>.json`.

---

## 3 · Carpetas

| Cuándo | Carpeta |
|---|---|
| Hay marca y modelo claros (A, B, estudio) | `informes\<marca>\<modelo>\` |
| Es descubrimiento (D) | `informes\descubrimiento\` |
| Es stock por categorías (E) | `informes\stock\` |
| Es estudio de mercado | `informes\mercado\` |
| Comparativas | junto al informe del encargo que las originó |

- **Los `.md` que lee el usuario van al Escritorio** (nunca a `AppData\…\outputs\`).
- Un informe **no se duplica**: si el usuario pide ampliar, se **actualiza el mismo fichero**
  (regla dura 23-ago-2026).

---

## 4 · Formato de entrega (una sola regla)

> 🔴 **Markdown (`.md`) y UN solo fichero.** PDF, `.docx`, ZIP o cualquier otro formato **solo
> si el usuario lo pide explícitamente** en ese encargo.
>
> Motivo: los enlaces de candidatos y de fuentes **no funcionan en PDF**, y en su día el usuario
> recibió 8 ficheros del mismo trabajo.

| Artefacto | Quién lo genera | Cuándo |
|---|---|---|
| Informe `.md` (búsqueda / unidad / modelos / mercado) | **Claude** | siempre |
| Esqueletos `.txt` del paquete (`ficha-publicitaria`, `dossier-cliente`, `redes-sociales`, `anuncio-portales`…) + `informe.json` dentro del ZIP | **Claude** | Flujo A con veredicto 🟢/🔵 |
| PDF de venta (ficha, dossier, folleto) | **Laravel** (Blade + Browsershot) | al importar el ZIP |
| PDF del informe de investigación | **nadie por defecto** | solo si el usuario lo pide |
| Publicación en portales/redes | **Laravel** | al importar el ZIP |

> ❌ No generar PDFs «porque queda mejor», ni una copia `.md` + otra `.pdf` del mismo trabajo,
> ni ZIP fuera del Flujo A.

---

## 5 · Checklist rápido antes de entregar

- [ ] ¿El entregable es el de **la fila correcta** de la tabla §1? (no un informe de valoración cuando tocaba búsqueda)
- [ ] ¿El nombre del fichero es el **canónico** de §2?
- [ ] ¿Está en la carpeta de §3?
- [ ] ¿Es **un solo `.md`** sin PDF ni duplicados?
- [ ] ¿Lleva el bloque **🔗 FUENTES** por modelo-versión (A33) y los **enlaces a ficha** (A21)?
- [ ] ¿He parado en el **checkpoint** correspondiente sin avanzar de fase?
