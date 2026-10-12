# 01 — SaaS vs Web pública: separar el producto del escaparate

> **Actualizado: 2026-08-21** — la separación YA está implementada en rutas. Ver "Estado real" al final.

## El problema que tú detectaste

En `/marketplace` (la web pública de JJ Import Motors) aparecía en el navbar un enlace **"Precios"** que lleva a `/pricing`... que son los **precios DEL SaaS** (los planes basic/pro/enterprise que pagan OTRAS inmobiliarias/importadoras).

Eso mezcla dos negocios distintos:

```
WEB PÚBLICA (jjimportmotors.com)
└── Cliente final: "quiero un BMW de Alemania"
    Precios relevantes: el precio DE LOS COCHES

SAAS (ImportnexCore)
└── Cliente B2B: "quiero gestionar MI importadora con este software"
    Precios relevantes: planes 29€/99€/enterprise
```

Un visitante del marketplace que ve "Plan Pro 99€/mes" piensa *"¿me van a cobrar 99€ por un coche?"* y se va.

## La decisión

**Un layout compartido (`PublicLayout.vue`) pero con navegación contextual.** La solución correcta no es borrar `/pricing`, es **no mostrarlo en el contexto equivocado**:

| Contexto | Navbar debería mostrar |
|---|---|
| `/` (landing del SaaS) | Features, **Pricing (SaaS)**, Login |
| `/marketplace` (escaparate de JJ) | Catálogo, Cómo funciona, **Contactar**, Pedir coche |
| `/pricing` | Solo accesible desde la landing del SaaS o desde dentro de la app |

### Estado actual (lo que hay)

`resources/js/Layouts/PublicLayout.vue` tiene **un solo navbar para todo lo público** → de ahí la mezcla que viste.

### Solución recomendada (pendiente de implementar)

1. **Dos layouts públicos** o un layout con prop `variant`:
   ```vue
   <PublicLayout variant="saas">      <!-- navbar: Features | Pricing | Login -->
   <PublicLayout variant="marketplace"> <!-- navbar: Catálogo | Contactar | Pedir coche -->
   ```

2. **Rutas separadas por dominio conceptual:**
   ```
   /                    → landing SaaS (¿vender el software?)
   /pricing             → planes SaaS
   /marketplace         → escaparate de la organización (coches)
   /marketplace/{car}   → ficha de coche
   /request/{slug}      → formulario "quiero este coche"
   ```

3. A largo plazo, lo ideal para un SaaS multi-tenant: **subdominios**
   - `jjimportmotors.importnexcore.com/marketplace` → escaparate de ese tenant
   - `importnexcore.com/pricing` → precios del SaaS
   
   Así cada organización tiene SU escaparate y el SaaS tiene el suyo. Es como Shopify: `mitienda.myshopify.com` ≠ `shopify.com/pricing`.

## El porqué (lo que te llevas)

> **Regla reutilizable:** Una app puede tener varios "productos" apuntando a audiencias distintas. Cada audiencia necesita su propio camino de navegación. Si un usuario puede llegar a una página que "no es para él", tu arquitectura de información está mezclando contextos. Separa por **audiencia**, no por tecnología.

### Señales de que estás mezclando contextos
- Un navbar con items que solo aplican a la mitad de tus páginas.
- CTAs que compiten ("Compra un coche" junto a "Contrata el software").
- Métricas imposibles: no sabes si `/pricing` convierte coches o suscripciones.

### Checklist para tu próxima app
- [ ] Define las audiencias (aquí: comprador de coche vs dueño de importadora).
- [ ] Cada audiencia tiene su "home" y su flujo de conversión.
- [ ] Ninguna página pública enlaza a un funnel que no es el suyo.

---

## Estado real (2026-08-21) — lo que está implementado

> Referencia para futuras tareas: **qué es SaaS y qué es Organización** en ImportnexCore.

### El SaaS (ImportnexCore) — el software que se vende

| Ruta | Página | Notas |
|---|---|---|
| `/` | Landing SaaS (`Welcome.vue`) | Hero + features + cómo funciona + precios + FAQ + testimonios. Navbar: **Precios** · Login · Registro |
| `/pricing` | Planes SaaS (`Public/PricingPublic`) | Renderiza `config('subscription.plans')` |
| `/login` `/register` | Auth SaaS | — |
| `/dashboard`, `/cars`, `/finance`, `/clients`, `/contacts`, `/trips`, `/alerts`, `/billing`… | App autenticada | Todo tras `auth + verified + organization` |
| `/admin` | Legacy → `redirect('/')` | — |

**Lo que vende:** gestión multi-tenant (stock, leads, finanzas, valoraciones IA, marketplace público de cada org). Los planes son **Starter 29€ · Professional 99€ · Enterprise 299€** (`config/subscription.php`). La landing SaaS NO promete los servicios de la organización (importación, transporte, entrega): eso es de cada tenant.

### La Organización (tenant) — el negocio del cliente sobre el SaaS

| Ruta | Página | Notas |
|---|---|---|
| `/marketplace` | Escaparate público de la organización | `PublicMarketplaceController`, con su marca y su copy |
| `/marketplace/{car}` | Ficha de coche | — |
| `/request/{slug}` | Formulario "quiero este coche" | — |
| `/mercado` | Catálogo "bajo pedido" | Skill `estudio-mercado` |

**Lo que vende cada organización:** su propio servicio (ej. JJ Import Motors: búsqueda/importación/entrega en España y Alemania). Ese copy NO debe aparecer en la landing SaaS (`/`).

### Regla de oro para no mezclar contextos
1. La landing SaaS (`/`) habla del **software** y enlaza a `/pricing` y `/register`.
2. El marketplace (`/marketplace`) habla de **coches** y enlaza a "Solicita tu vehículo" / contacto.
3. Ningún navbar enlaza al funnel del otro.
4. Datos multi-tenant SIEMPRE aislados por `organization_id`.
