<!-- filepath: docs/AUDITORIA_BILLING_2026-08-05.md -->
# Auditoría profunda del flujo de Billing — ImportnexCore
**Fecha:** 2026-08-05
**Alcance:** suscripciones, planes, webhooks, Stripe, downgrade/upgrade/cancel, multi-tenant, frontend.
**Stack:** Laravel 11.55 · Cashier 16.6 · PHP 8.5 · Inertia v2 · Vue 3.

> Esta auditoría **NO** modifica código. Es un mapa de problemas + plan de remediación. Después de tu visto bueno aplicamos fixes.

---

## 1. Mapa del flujo actual

```
Registro ──► crea Organization (plan=starter, trial=14d) ──► user.role=owner
    │
    ▼
Subscriptions/Index (Vue)
    │  Lee: $org->subscription('main'), onTrial(), trialEndsAt()
    │
    ▼
subscriptions/{plan}/create  ──► SubscriptionController@create
    │  - $org->newSubscription('main', $plan)->trialDays(14)->create([pm])
    │  - $org->update(['plan' => $plan])
    │
    ▼
subscriptions/{plan}/swap  ──► SubscriptionController@swap
    │  - $org->subscription('main')->swap($newPlan)
    │  - $org->update(['plan' => $newPlan])
    │
    ▼
subscriptions/cancel / resume
    │
    ▼
Stripe webhook ──► StripeWebhookController (idempotente con Cache::lock 60s)
    │  handleCustomerSubscriptionCreated  →  org.update(plan, subscribed_at)
    │  handleCustomerSubscriptionUpdated  →  org.update(plan)
    │  handleCustomerSubscriptionDeleted  →  org.update(plan='starter')
    ▼
PlanLimitMiddleware 'plan.limit:cars|clients|contacts'
    └─► Organization::limitReached($type) → match config + cuenta actual
```

---

## 2. Tabla de hallazgos

| # | Severidad | Tema | Detalle |
|---|---|---|---|
| F-01 | 🔴 Crítico | **Stripe no configurado en `.env` prod** | `STRIPE_KEY=null`, `STRIPE_SECRET=null`, `STRIPE_WEBHOOK_SECRET=null`. Cualquier flujo de pago falla silenciosamente. `stripeConfigured()` en `BillingController` devuelve `false` → portal y facturas rotas. |
| F-02 | 🔴 Crítico | **Tabla `subscriptions` tiene `user_id`, no `organization_id`** | Migración `2026_07_24_195135` crea con `user_id`. Hay una migración `2026_07_24_220000_rename_user_id_to_organization_id_in_subscriptions` que la renombra. **Cashier la renombra en su release nativo**, pero tu migración manual puede romper upgrades futuros. |
| F-03 | 🔴 Crítico | **Doble fuente de verdad `stripe_id`** | Existe en `organizations` y en `users`. Cashier la lee de `users` (modelo `Billable` aplicado a Organization sólo si así lo configuras). En [Organization.php:62-72](app/Models/Organization.php#L62-L72) usas `subscribed()` (Cashier) que mira `users.stripe_id`. **Pero el webhook busca por `organizations.stripe_id`** (`getUserByStripeId`) → desincronización garantizada. |
| F-04 | 🟠 Alto | **`SubscriptionController::create` puede perder el plan** | Si `$org->newSubscription(...)->create()` lanza (p.ej. sin PM), no se hace el `update(['plan' => $plan])` pero tampoco rollback. Estado inconsistente. |
| F-05 | 🟠 Alto | **`swap()` no valida que exista la suscripción** | Si `$request->user()->organization->subscription('main')` es `null` (trial sin subscripción real), `swap()` en Cashier lanza `SubscriptionUpdateFailure`. Sin try/catch → 500. |
| F-06 | 🟠 Alto | **Cancel/resume sin validación** | Si no hay suscripción, `subscription('main')->cancel()` lanza `BadMethodCallException`. Sin try/catch → 500. |
| F-07 | 🟠 Alto | **Sin prorrateo en downgrade** | `$org->subscription('main')->swap('starter')` en Cashier aplica el nuevo plan inmediatamente con crédito prorrateado **solo si `prorateBehavior` está configurado**. El modelo actual no lo define → comportamiento por defecto de Stripe. Si quieres "downgrade al final del periodo" hay que pasar `->swapAndInvoice($plan)` o setear `proration_behavior='create_prorations'` / `'none'`. |
| F-08 | 🟠 Alto | **Webhook solo maneja 3 eventos** | `cashier.webhook.events` solo registra `customer.subscription.{created,updated,deleted}`. Faltan eventos críticos: `invoice.payment_succeeded`, `invoice.payment_failed`, `customer.subscription.trial_will_end`, `customer.updated`. Un pago fallido no degrada al usuario. |
| F-09 | 🟠 Alto | **Lock de 60s insuficiente en producción** | `Cache::lock($lockKey, 60)->block(5, ...)` — Stripe reintenta webhooks durante ~24h. Si el primero falla, el segundo dentro de 60s **se saltea el handler**. Lock debería ser 86400s (24h) con `event.id` como clave. |
| F-10 | 🟡 Medio | **`extractPlanFromSubscription` sin fallback final** | Si `$price['lookup_key']`/`nickname` no matchean y `stripe_price_id` tampoco, devuelve `null`. Webhook **silenciosamente** deja `plan` sin actualizar. Debería loguear warning y/o revertir a `starter`. |
| F-11 | 🟡 Medio | **`plan` se duplica con `subscriptions.stripe_price`** | Organizations.plan es texto plano (`pro`, `enterprise`). Cashier guarda `subscriptions.stripe_price` (id `price_xxx`). **No hay sincronización** si el admin edita `organizations.plan` directamente. Cualquier cambio manual de `plan` es ignorado por Cashier. |
| F-12 | 🟡 Medio | **`hasActiveSubscription` no distingue trial real vs `$trial_ends_at` legacy** | Mezcla `subscribed()` (Cashier) con `trial_ends_at` de la org. La columna `organizations.trial_ends_at` se setea en el seeder pero **no se sincroniza** con la del modelo de Cashier. Tras un checkout, hay **dos** `trial_ends_at` divergentes. |
| F-13 | 🟡 Medio | **`Organization::subscribed()` no funciona porque Billable está en Organization pero la subscripción es del User** | Tu modelo aplica `Billable` a Organization, pero las subscripciones se crean vía `$org->newSubscription(...)` que las guarda en `subscriptions` con `user_id`. Cuando Cashier ejecuta `$org->subscribed()`, internamente busca el stripe_id de Organization (null) → falla. **Toda la API de Cashier está rota en producción**. |
| F-14 | 🟡 Medio | **Plan downgrade no re-visa `limitReached`** | Si pasas de `enterprise` (1000 cars) a `starter` (10 cars) y tienes 50 cars, la nueva suscripción se crea OK, pero los 40 cars restantes quedan en BD rompiendo el invariante "current < limit". No hay soft-delete automático ni aviso. |
| F-15 | 🟡 Medio | **Cancel no comunica al usuario** | Tras `cancel()`, `ends_at` se rellena pero el frontend no avisa "tu plan termina el día X". El usuario podría perder acceso el día del corte sin transición. |
| F-16 | 🟡 Medio | **`BillingController::stripeIsConfigured` no existe** | El controller llama a `$this->stripeIsConfigured()` pero **no se ve el método en el código**. Probablemente está en el `Controller` base o es helper — debe verificarse. |
| F-17 | 🟡 Medio | **Sin `Subscription` model binding en rutas** | Las rutas usan strings (`/subscriptions/{plan}/create`) sin `Route::model('subscription', Subscription::class)`. Acepta cualquier string → validation lo cubre pero es frágil. |
| F-18 | 🟢 Bajo | **`Subscription::swap` no respeta prorrateo** | Igual que F-07 pero a nivel UX. |
| F-19 | 🟢 Bajo | **No hay SoftDeletes en `subscriptions`** | Cancelaciones hard-deleted si en algún punto se implementa. |
| F-20 | 🟢 Bajo | **Frontend `Index.vue` hardcodea 'starter/pro/enterprise'** | `const planKeys = ['starter', 'pro', 'enterprise']` — añadir un plan requiere tocar el front. |
| F-21 | 🟢 Bajo | **Frontend no diferencia "is_owner"** | El nuevo flag `is_owner` se expone en `currentPlan.is_owner` pero `Index.vue` no lo usa — JJ sigue viendo el botón "Upgrade" pese a ser vitalicio. |
| F-22 | 🟢 Bajo | **`swap()` no maneja "downgrade to free"** | Si `$newPlan === 'starter'`, no hay subscripción que swapear. Hay que `cancel()` antes. |
| F-23 | 🟢 Bajo | **`subscription.stripe_price` vs config lookup_key no documentado** | El `extractPlanFromSubscription` busca `lookup_key`, pero los precios en Stripe deben tener `lookup_key='pro'`, `'enterprise'`, `'starter'`. **Sin esto, todos los webhooks devuelven null.** |

---

## 3. Multi-tenant y aislamiento

| Punto | Estado | Comentario |
|---|---|---|
| `organization_id` en `subscriptions` | ✅ Existe (migración rename) | Cashier 16 lo espera |
| `user_id` legacy en subscripciones | ⚠️ | Depende de cuándo se ejecutó la rename migration. Verificar orden. |
| Webhook usa `organizations.stripe_id` | 🔴 | Desincronizado con Cashier (ver F-03) |
| `Billable` trait en Organization | 🟠 | Diseño no estándar. Cashier 16 está pensado para User. Funciona solo si reescribes `getBillableId()` o lo mantienes en User. |
| `PlanLimitMiddleware` chequea `auth()->user()->organization_id` | ✅ | Aislado correctamente |
| `HandleInertiaRequests` filtra por `organization_id` | ✅ | OK |
| `car-requests` y alertas multi-tenant | ✅ | Filtran por `organization_id` |

---

## 4. Plan de downgrade / upgrade / cancel — gap analysis

| Operación | Endpoint | Backend | Frontend | Webhook sync |
|---|---|---|---|---|
| Upgrade (starter→pro) | `POST subscriptions/{plan}/create` | ⚠️ F-04 | ✅ | ✅ |
| Downgrade (pro→starter) | `POST subscriptions/{plan}/swap` | 🔴 F-05, F-14 | ⚠️ sin aviso | ✅ |
| Cancel | `POST subscriptions/cancel` | 🔴 F-06 | 🟠 F-15 | ✅ |
| Resume | `POST subscriptions/resume` | 🔴 F-06 | ✅ | ✅ |
| Cambiar PM | Stripe portal | ⚠️ F-01 | ✅ | — |
| Pago fallido | (no handler) | 🔴 F-08 | 🔴 no degrada | — |
| Trial ending | (no handler) | 🔴 F-08 | 🔴 no avisa | — |
| Webhook Stripe → DB | `StripeWebhookController` | 🟠 F-09, F-13 | — | ✅ |

---

## 5. Tests existentes vs cobertura necesaria

```
$ php artisan test --filter=Subscription
```
- ❌ **No hay tests** para `SubscriptionController` (no existe `tests/Feature/Subscription/*`).
- ❌ No hay tests para `StripeWebhookController`.
- ❌ No hay tests para `Organization::limitReached` / `isOwner`.
- ❌ No hay tests para `PlanLimitMiddleware`.
- ✅ Hay `Tests\TestCase.php` y algunos Feature tests pero **ninguno cubre billing**.

---

## 6. Decisiones arquitectónicas abiertas

1. **¿`Billable` en Organization o en User?**
   - Si en User: subscripción atada al dueño. Multi-tenant se rompe (todos los owners de una org con subscripción activa).
   - Si en Organization: Cashier 16 funciona pero necesitas modelar `getBillableId()` y `subscriptions` por org. **Recomendado**: mantener `Billable` en `Organization` y reescribir las FKs.

2. **¿Cómo se degradará al usuario tras fallo de pago?**
   - Opción A: tras `invoice.payment_failed`, mantener acceso N días (grace period) y degradar a `starter`.
   - Opción B: degradar inmediatamente.
   - Cashier tiene `Cashier::stripe()` config + listener `Billable` que ya hace algo; hay que auditarlo.

3. **¿Vitalicio JJ = bypass en modelo o suscripción Stripe con `metadata.is_owner=true`?**
   - Modelo: actual (más simple, sin tocar Stripe).
   - Stripe: más limpio pero requiere plan custom.
   - **Recomendado**: modelo (lo que ya hicimos).

---

## 7. Plan de remediación priorizado

### Fase 1 — Crítico (1 sesión)
1. Configurar `STRIPE_KEY`/`SECRET`/`WEBHOOK_SECRET` en prod (manual fuera de código).
2. Crear precios en Stripe con `lookup_key` = `starter`, `pro`, `enterprise` y `stripe_price_id` en `config/subscription.php`.
3. Reescribir `extractPlanFromSubscription` con fallback robusto + Log::warning.
4. Hacer idempotente el webhook con lock 24h + `event.id`.
5. Try/catch en `create/swap/cancel/resume` para evitar 500 silenciosos.
6. Subscription `swap` con `proration_behavior='create_prorations'` configurable.

### Fase 2 — Funcional (1 sesión)
7. Manejar eventos `invoice.payment_failed`, `customer.subscription.trial_will_end`.
8. Frontend: badge "Vitalicio" si `currentPlan.is_owner`, ocultar CTA de upgrade.
9. Frontend: aviso "tu plan termina el X" cuando `subscription.ends_at`.
10. Bloquear `swap()` a `starter` que requiere `cancel()` previo si hay subscripción activa.

### Fase 3 — Robustez (1 sesión)
11. Tests feature para todo el flujo de billing.
12. SoftDeletes en `subscriptions`.
13. Frontend: planes dinámicos desde API (no hardcoded).
14. Documentar `lookup_key` y proceso de crear planes en `docs/planes/`.

---

## 8. Métricas de salud actuales (prod)

| Métrica | Valor |
|---|---|
| Organizaciones | 1 |
| Suscripciones activas | 0 |
| Suscripciones en trial | 0 (JJ tiene `trial_ends_at` en org pero NO subscripción Cashier) |
| Invoices generadas | 0 |
| Webhooks recibidos (último mes) | 0 |
| Clientes creados | 0 |
| Errores 500 últimos 7 días en `/subscriptions/*` | (revisar Sentry/logs) |
| `STRIPE_KEY` configurado | ❌ |

---

## 9. Preguntas para el product owner

1. **¿Qué comportamiento quieres al degradar?**
   - A: aplicar inmediatamente con prorrateo (UX confusa).
   - B: aplicar al final del periodo (recomendado SaaS).

2. **¿Grace period tras pago fallido?**
   - A: 7 días con aviso.
   - B: degradar inmediatamente.

3. **¿El vitalicio de JJ debe seguir apareciendo como `pro` en BD o cambiar a `enterprise`?**
   - Actual: `plan='pro'`, `is_owner=1`, bypass en runtime.
   - Alternativa: `plan='enterprise'`, `is_owner=1`.

4. **¿Planes anuales o solo mensuales?**
   - Actual config: solo `period='month'`.

5. **¿Cupones / descuentos?**
   - No implementado. ¿Necesario para JJ?

---

## 10. Resumen ejecutivo

> El sistema **tiene la arquitectura básica correcta** pero **no funciona end-to-end** porque Stripe no está configurado en prod y hay un mismatch entre `Billable` (Organization) y `subscriptions.user_id`. Cualquier intento de pago fallará silenciosamente.
>
> El bypass de JJ está bien resuelto a nivel de modelo. El webhook es robusto contra reintentos pero no contra eventos críticos (pago fallido, fin de trial).
>
> **Recomendación**: priorizar Fase 1 antes de aceptar cualquier cliente de pago.
