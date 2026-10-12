# Guía 09 — Skill `ecommerce-tuning` (tienda tramitadora)

> **Para:** Tienda online de accesorios/tuning de coches (España)
> **Qué es:** uso diario de la skill `ecommerce-tuning` con Claude + navegador para investigar productos viables, generar fichas y copy de marketing, **sin comprar stock**.
> **Actualizado:** 2026-09-16 (skill **v0.1.0**)
> **Documento maestro del negocio:** [`../ecommerce.md`](../ecommerce.md)
> **Reglas duras:** [`.claude/skills/ecommerce-tuning/SKILL.md`](../../.claude/skills/ecommerce-tuning/SKILL.md) — E1 sin stock, E2 aprobación humana, E6 costes completos.

---

## Índice

| Sección | Contenido | Cuándo leerla |
|---|---|---|
| §1 Arranque y sincronización | Verificar ZIP instalado y memoria cargada | Primera vez + cada sesión |
| §2 Lo que NO es esta skill | Diferencias con `importacion-vehiculos` | Antes de empezar |
| §3 Cómo pedirlo (frases que disparan cada flujo) | Disparadores S / P / F / M | A diario |
| §4 Flujos S · P · F · M en cascada | Qué entrega cada uno y dónde paran | A diario |
| §5 El filtro de viabilidad | Las 3 condiciones innegociables | Antes de aprobar un producto |
| §6 Navegador: interno vs Claude para Chrome | Cuándo usar cada uno | Cuando una página no carga |
| §7 Memoria de la skill | Qué guardar, dónde | Tras cada evaluación |
| §8 Marketing multicanal (Meta + TikTok) | Fases y umbrales | Al lanzar una campaña |
| §9 Errores comunes | Lo que ya rompe cosas | Cuando algo sale mal |

---

## 1. Arranque y sincronización

**Antes de empezar**, verifica que la skill está cargada en la sesión:

- En Claude Desktop: el nombre `ecommerce-tuning` aparece en la lista de skills habilitadas. Si no, importar el ZIP de `.claude/skills/_dist/` (el último es `skills-ecommerce-tuning-v0.1.0-YYYYMMDD.zip`) y descomprimirlo en `%USERPROFILE%\.claude\skills\`.
- En Cowork: misma ZIP subida al gestor de skills de la cuenta.

> Regla de la skill (`.ai/rules/skills-sync.md`): **si tocas el ZIP a mano, deja siempre un único fichero por skill en `_dist/`**. El script `.\scripts\build-skill-zips.ps1` borra los antiguos.

**Memoria primero (regla E9):** la skill tiene su memoria en `.claude/skills/ecommerce-tuning/memoria/`. Antes de navegar, debe leer:

- `productos-evaluados.md` — no re-evaluar lo ya rechazado salvo cambio de precio o proveedor
- `proveedores.md` — no re-verificar un proveedor ya descartado
- `trampas.md` — no insistir contra un anti-bot o una página que ya rompió

Si la memoria está vacía, normal — se va rellenando con el primer uso.

---

## 2. Lo que NO es esta skill (para no mezclarla con `importacion-vehiculos`)

| Concepto | `ecommerce-tuning` | `importacion-vehiculos` |
|---|---|---|
| Negocio | Tienda online (dropshipping) | Tramitar búsqueda + importación de coches |
| Producto | Accesorios/tuning para vender | Coches que el cliente quiere comprar |
| Quién compra | El cliente final de la tienda | El cliente del servicio |
| Modelo | Tramitador puro (0 stock) | Tramitador puro (0 stock de coches) |
| Fuentes | TikTok CC, Meta Ads, Trends, 1688, Amazon.es | mobile.de, AS24, Coches.net, etc. |
| Coste crítico | Margen de venta, CPA publicitario | Hueco de importación, IEDMT |
| Aprobación humana | Antes de publicar / lanzar ads / pedir | Antes de entregar informe final |
| Memoria | Productos + proveedores + trampas | Modelos + vendedores + errores |

**Regla de oro:** si un encargo empieza con un **anuncio de coche**, va a `importacion-vehiculos`. Si empieza con un **producto de tuning o accesorio**, va a `ecommerce-tuning`. No comparten criterios, costes ni memoria.

---

## 3. Cómo pedirlo — frases que disparan cada flujo

No hace falta aprender jerga. Describe lo que quieres con tus palabras:

| Lo que quieres | Dile al skill | Flujo |
|---|---|---|
| Saber **qué productos se están moviendo** esta semana | *"Escanea tendencias de tuning de esta semana"* / *"Qué merece la pena mirar ahora"* | **S · SEÑALES** |
| **Evaluar un producto** que viste viral o te pasaron | *"Evalúa este producto viral [enlace] y dime si vale la pena"* | **P · PRODUCTO** |
| **Localizar proveedor** para un producto concreto | *"Busca proveedor de [producto] en 1688/Alibaba/CJ"* | **P · PRODUCTO** |
| Generar la **ficha SEO** lista para Shopify | *"Ficha para [producto]"* (tras P aprobado) | **F · FICHA** |
| Crear **anuncios y copy** para redes | *"Anuncios para [producto]"* / *"Copy para IG/TikTok/Meta"* (tras F aprobado) | **M · MARKETING** |
| Lanzamiento **completo** | *"Lanza el pack completo de [producto]"* | S → P → F → M con paradas de aprobación |

> **Si tu encargo no encaja**, la skill te pregunta. No improvisa.

---

## 4. Los 4 flujos en cascada

```
[S · Señales]   → shortlist 5-10 candidatos
       ↓ (tú eliges 1-3)
[P · Producto]  → veredicto ✅/❌/⚠️ con filtro completo
       ↓ (tú apruebas)
[F · Ficha]     → borrador Shopify + galería + compatibilidad
       ↓ (tú apruebas)
[M · Marketing] → 3 variantes Meta + TikTok guion + IG caption + Google Shopping
       ↓ (tú apruebas antes de publicar/lanzar)
```

**Regla dura (E2):** la skill **nunca** publica, **nunca** lanza anuncios, **nunca** envía mensajes a proveedores con intención de pedido. Cada `→` es un **checkpoint de aprobación humana**. Si ves que se lo salta, recházalo.

### ¿Qué entrega cada flujo?

| Flujo | Output | Dónde |
|---|---|---|
| S | `informe-senales.md` con shortlist + métricas + enlaces | `informes-ecommerce/YYYY-MM-DD-<slug>/` |
| P | `informe-viabilidad-<slug>.md` con filtro + veredicto + proveedor | igual |
| F | `ficha-<slug>.md` lista para pegar en Shopify (draft) | igual |
| M | `marketing-<slug>.md` con copy multicanal | igual |

Cada entrega va a la misma carpeta de sesión para que tú tengas todo el rastro.

---

## 5. El filtro de viabilidad (las 3 condiciones)

**Antes de aprobar un candidato**, verifica que cumple las 3 simultáneamente (regla E5). Sin excepciones "porque está de moda":

1. **Score ≥ 7,5** (de la fórmula §14.2 de la guía)
2. **Ratio P_venta / (C_producto + C_envío) ≥ 3×**
3. **Sin restricciones logísticas** (peso >1kg, baterías de litio, líquidos → fuera)

**Matiz tramitador:** si un proveedor con almacén UE da margen bruto ≥40%, gana el desempate aunque el ratio sea menor. Menos plazo, menos devolución, menos soporte.

**Costes obligatorios** (regla E6): el margen SIEMPRE con arancel + IVA import + gestión aduanera + pasarela + CPA + provisión devoluciones. Si ves un margen sin esos, pídelo recalculado.

Más detalle: [`../ecommerce.md`](../ecommerce.md#142-algoritmo-de-viabilidad-financiera-el-filtro).

---

## 6. Navegador: interno vs Claude para Chrome

La skill usa **el navegador interno de Claude por defecto**. Cambia a **Claude para Chrome** solo cuando:

| Situación | Navegador |
|---|---|
| Página normal que renderiza bien | Interno |
| Hace falta login (1688, Alibaba para mensajes, CJdropshipping) | Claude para Chrome |
| JS pesado que el interno no renderiza | Claude para Chrome |
| Anti-bot (TikTok a veces, Ads Library a veces) | Claude para Chrome |
| Sitios que ya rompieron en sesiones previas (ver `memoria/trampas.md`) | Degradar a otra fuente, NO insistir |

**Regla E3:** ritmo humano, clic + espera + screenshot antes de concluir. Si una página no carga, degradar (versión móvil, otra fuente) y anotar en `memoria/trampas.md`. No insistir en bucle.

**Regla E4 (Claude para Chrome):** tu Chrome real tiene tus sesiones. La skill **no debe cerrar sesión ni hacer logout** de nada. Si ve un portal donde ya estás logado, lo aprovecha; si no, lo pide o degrada.

---

## 7. Memoria de la skill

Regla E9: **consultar memoria ANTES de navegar**, **actualizar memoria DESPUÉS de cada flujo**.

Cuándo actualizar:

| Flujo | Qué guardar |
|---|---|
| S | Señales nuevas con enlace (en `trampas.md` o nota aparte si quieres) |
| P | Producto evaluado (aprobado o rechazado) en `productos-evaluados.md` y proveedor en `proveedores.md` |
| F | — (no aporta a la memoria salvo incompatibilidades detectadas) |
| M | — (los creativos ganadores salen de `marketing-<slug>.md`, archivado) |

**Por qué importa:** un producto rechazado en enero puede reaparecer viral en junio. La memoria evita re-hacer el trabajo y detecta cambios de precio o proveedor.

---

## 8. Marketing multicanal (Meta + TikTok)

La guía completa con fases, presupuesto y umbrales está en [`../ecommerce.md`](../ecommerce.md#plan-de-marketing-multicanal-meta--tiktok--a%C3%B1adido-16-sep-2026). Resumen:

| Fase | Qué | Cuándo |
|---|---|---|
| 0 | Orgánica IG + TikTok, 3-5 piezas/semana, pixel instalado | Semanas 1-2 (0€) |
| 1 | Meta Ads 10-15€/día, estructura por categoría, 3 creativos por conjunto | Semana 3+ |
| 2 | TikTok Ads 10-20€/día con Spark Ads sobre lo orgánico ganador | Semana 4+ |
| 3 | Retargeting a visitantes 7d + carrito 7d, 5€/día | Cuando el pixel tenga ~500 visitantes |

**Umbrales de decisión sin excepciones:**

- ROAS ≥3 tras 3 días → subir presupuesto +20-30% cada 48h
- ROAS 2-3 → mantener y testear creativo nuevo
- ROAS <2 tras 50€ gastados → matar conjunto/creativo
- CPC >0,80€ sostenido → creativo quemado, rotar
- CPA > margen bruto → producto o público equivocado, fuera

---

## 9. Errores comunes

| Error | Lo que rompe | Cómo evitarlo |
|---|---|---|
| "Está de moda, lo meto igual" | Filtro E5 → catálogo muerto | Aplicar las 3 condiciones, sin excepciones |
| Margen sin aranceles/pasarela/CPA | Beneficio ficticio → pérdida real | E6: siempre el cálculo completo |
| "Mejor pido 20 unidades" | Modelo roto (E1) | Tramitador puro: best-seller → almacén UE |
| "Ya lancé los ads porque eran buenísimos" | Cuenta de Meta quemada o gasto inútil | E2: aprobación humana antes de gastar |
| Re-evaluar un producto sin mirar memoria | Tiempo y peticiones tirados | E9: leer `memoria/` antes de navegar |
| Insistir contra anti-bot | Bucle de tiempo y tokens | E3: degradar y anotar |
| Mezclar criterios con `importacion-vehiculos` | Informes cruzados, decisiones malas | §2 de esta guía: separar memorias |
| Publicar ficha sin verificar compatibilidad (marca/modelo/año) | Devoluciones + reseñas negativas | F: bloque compatibilidad obligatorio |

---

## Resumen de 30 segundos

1. **Arranca** verificando que la skill está cargada y la memoria consultada.
2. **Dile qué quieres** con tus palabras: escanear tendencias (S), evaluar producto (P), ficha (F), anuncios (M) o pack completo.
3. **Recibe el entregable** del flujo correspondiente con el filtro aplicado y veredicto.
4. **Aprueba o rechaza** cada `→` antes de avanzar al siguiente flujo.
5. **Actualiza la memoria** tras cada evaluación para no re-hacer el trabajo.
6. **Lanza en fases** (orgánica → Meta → TikTok → retargeting) y mata lo que no dé ROAS.
