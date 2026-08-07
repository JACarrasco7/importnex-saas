# Stripe Webhooks — Quick Reference (importnex-cashier-billing skill)

> Referencia rápida. Skill completa en SKILL.md.

## Endpoint
```
POST /stripe/webhook → StripeWebhookController@handleWebhook
```

## Eventos procesados

| Evento | Handler | Acción |
|---|---|---|
| `customer.subscription.updated` | `handleUpdated()` | Actualizar plan, items, trial |
| `invoice.payment_failed` | `handlePaymentFailed()` | Marcar `payment_failed_at` en org |
| `customer.subscription.deleted` | `handleDeleted()` | Cancelar suscripción |
| `invoice.payment_succeeded` | `handlePaymentSucceeded()` | Limpiar `payment_failed_at` |

## Idempotencia

```php
$event = WebhookEvent::firstOrCreate(
    ['stripe_id' => $request->input('id')],
    ['type' => $request->input('type'), 'payload' => $request->all()]
);

if (! $event->wasRecentlyCreated) {
    return response('Duplicate', 200);
}
```

## Verificación local

```bash
# Stripe CLI
stripe listen --forward-to localhost:8000/stripe/webhook

# Disparar evento
stripe trigger customer.subscription.updated

# Verificar firma
stripe verify --event-id=evt_xxx
```

## Headers requeridos

```
Stripe-Signature: t=123456789,v1=abcdef...,v0=...
Content-Type: application/json
```
