# Billing Rules — Cashier 16 + Stripe

> Activar por glob: `app/Http/Controllers/StripeWebhookController.php`, `app/Services/Billing/**`, `app/Helpers/SubscriptionHelper.php`, `app/Listeners/*Subscription*`, `config/subscription.php`

---

## Regla de oro

**Webhook idempotente + verificar firma SIEMPRE.** Sin esto: doble cargo, doble suscripción, caos.

## Estructura obligatoria

```php
public function handle(Request $request): Response
{
    // 1. Verificar firma
    if (!$this->verifySignature($request)) {
        return response('Invalid signature', 400);
    }

    // 2. Idempotencia
    $event = WebhookEvent::firstOrCreate(
        ['stripe_id' => $request->input('id')],
        ['type' => $request->input('type'), 'payload' => $request->all()]
    );
    if (!$event->wasRecentlyCreated) {
        return response('Duplicate', 200); // sin reprocesar
    }

    // 3. Dispatch por tipo
    try {
        match ($request->input('type')) {
            'customer.subscription.updated' => $this->handleUpdated($event),
            'invoice.payment_failed' => $this->handlePaymentFailed($event),
            'customer.subscription.deleted' => $this->handleDeleted($event),
            default => null,
        };
        $event->update(['processed_at' => now()]);
    } catch (\Throwable $e) {
        $event->update(['error' => $e->getMessage()]);
        throw $e; // Stripe reintentará
    }

    return response('OK', 200);
}
```

## Planes

- **Vitalicio = `is_owner=true`** en organizations (no flag separado).
- **Plan ilimitado bypassa** `PlanLimitMiddleware`.
- **Basic, Pro, Enterprise** — config en `config/subscription.php`.

## Trial

- **14 días** default (configurable).
- `trial_ends_at` en organizations.

## Dunning (ES-first)

- **Email `payment_failed`** en español.
- **Grace period 7 días** antes de cancelar.
- **NO desuscribir inmediato** — solo abrir ventana.
- **Reintento semanal** automático.

## Cambio de plan

- **Downgrade seguro**: `$org->subscription()->swap($newPlanId)`.
- **NUNCA** borrar datos al cambiar de plan.
- **Mantener** acceso hasta fin de periodo (cancel, no revoke inmediato).

## Verificación de plan

```php
$org = auth()->user()->organization;

if ($org->is_owner) return; // unlimited

if (!$org->subscribedToPrice($planId)) {
    abort(402, 'Plan upgrade required');
}
```

## Tests obligatorios

- `tests/Feature/Billing/WebhookIdempotencyTest.php` ← debe pasar
- `tests/Feature/Billing/PlanSwapTest.php`
- `tests/Feature/Billing/PaymentFailedGraceTest.php`

Comando: `php artisan test --compact --filter=Billing`.

## NO HACER

- ❌ Webhook sin verificar firma.
- ❌ Webhook sin `firstOrCreate`.
- ❌ `User::find($stripeCustomerId)` directo.
- ❌ Cobrar al cancelar inmediatamente.
- ❌ Tests con `STRIPE_KEY=sk_live_...` (usar `sk_test_...`).
- ❌ Dunning en inglés.
- ❌ Modo sandbox sin `STRIPE_WEBHOOK_SECRET` real.
