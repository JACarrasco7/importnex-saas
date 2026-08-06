---
name: importnex-cashier-billing
description: Billing con Laravel Cashier 16 sobre Stripe. Aplica cuando se habla de subscription, plan, suscripción, Stripe, webhook, idempotencia, dunning, payment_failed, is_owner, vitalicio, grace period, downgrade safe, billing portal, Cashier, upgrade, downgrade, cambio de plan, prorrateo, tarjeta fallida, SCA, 3DS, invoice, charge, refund, customer en Stripe, payment_method, SetupIntent, suscripción trial, trial_ending.
---

# Billing ImportnexCore (Cashier 16 + Stripe)

## Stack

- `laravel/cashier` v16.6 (compatible Laravel 13).
- `stripe/stripe-php` SDK oficial.
- Tablas: `subscriptions`, `subscription_items`, `customers` (gestionado por Cashier).
- Webhooks en `routes/web.php` con `Route::post('/stripe/webhook', ...)`.

## Reglas críticas (NUNCA violar)

1. **Idempotencia obligatoria en webhooks.** Usar `WebhookEvent::firstOrCreate(['id' => $event->id])` antes de procesar.
2. **Verificar firma SIEMPRE**: `$request->validate(...)` con `StripeWebhookController` o `verify_signature`.
3. **El plan vitalicio lo marca `is_owner=true`** en `organizations`. NO usar `has_unlimited_plan` separado.
4. **Plan ilimitado = bypass de `PlanLimitMiddleware`** con check `if ($org->is_owner)`.
5. **Downgrade seguro**: pasar de pro → basic NUNCA borra datos, solo cambia capabilities.
6. **Trial de 14 días por defecto** (configurable en `config/subscription.php`).
7. **Dunning en español** (cliente es ES-first). Grace period 7 días antes de cancelar.
8. **Email `payment_failed`** no desuscribe inmediatamente; abre ventana de gracia.

## Archivos críticos

```
app/Http/Controllers/StripeWebhookController.php   # Webhook entrante
app/Http/Controllers/SubscriptionController.php   # CRUD suscripciones
app/Http/Middleware/PlanLimitMiddleware.php        # Límites por plan
app/Helpers/SubscriptionHelper.php                  # Helpers de plan
app/Listeners/                                      # Eventos de billing
app/Models/Organization.php                          # is_owner
app/Models/Subscription.php                          # Wrapper de Cashier
config/subscription.php                             # Plans, precios, features
database/migrations/2026_08_05_*billing*            # Migraciones
routes/web.php                                      # POST /stripe/webhook
```

## Patrón: Webhook idempotente

```php
public function handle(Request $request): Response
{
    $event = WebhookEvent::firstOrCreate(
        ['stripe_id' => $request->input('id')],
        ['type' => $request->input('type'), 'payload' => $request->all()]
    );
    if ($event->wasRecentlyCreated === false) {
        return response('Duplicate', 200);
    }

    match ($request->input('type')) {
        'customer.subscription.updated' => $this->handleUpdated($event),
        'invoice.payment_failed' => $this->handlePaymentFailed($event),
        'customer.subscription.deleted' => $this->handleDeleted($event),
        default => null,
    };

    return response('OK', 200);
}
```

## Patrón: Verificar plan del usuario

```php
$org = auth()->user()->organization;

if ($org->is_owner || $org->subscribedToPrice($planId)) {
    return $this->allow();
}

abort(402, 'Plan upgrade required');
```

## Patrón: Downgrade seguro

```php
// Al cancelar, NO borrar datos
$org->subscription->cancel();  // queda activo hasta fin de periodo

// Al cambiar de pro → basic, mantener cars/clients/etc
$org->subscription()->swap($basicPlanId);
```

## Anti-patrones (NUNCA)

- ❌ Procesar webhooks sin verificar firma.
- ❌ Cobrar en testing con tarjeta real (usar `STRIPE_SECRET=sk_test_...`).
- ❌ `User::find($stripeCustomerId)` directo (usar Cashier `Customer::find`).
- ❌ `charge()` manual (usar `Payment::create()` o Cashier).
- ❌ Modo sandbox sin `STRIPE_WEBHOOK_SECRET` real.

## Variables de entorno

```
STRIPE_KEY=pk_test_...
STRIPE_SECRET=sk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...
CASHIER_CURRENCY=eur
CASHIER_CURRENCY_LOCALE=es_ES
```

## Comandos útiles

- `php artisan cashier:webhook` (registra el endpoint en Stripe).
- `php artisan migrate` (crea tablas Cashier si no existen).
- `php artisan tinker --execute="auth()->user()->subscription()->swap('price_basic')"`

## Auditoría 2026-08-05

Fases 1-3 de billing hardening aplicadas:
- Idempotencia webhooks ✅
- Plan ilimitado bypass ✅
- Dunning en español con grace period ✅
- Cancel honest page ✅
- Tabla comparativa planes ✅
