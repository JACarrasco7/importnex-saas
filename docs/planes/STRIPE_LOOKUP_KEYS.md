# Plan configuration & Stripe lookup_keys

> Last updated: 2026-08-05
> Audience: anyone creating a new plan or wiring up Stripe.

## How plans map to Stripe prices

Each plan in [`config/subscription.php`](../../config/subscription.php) has two Stripe-related keys:

```php
'pro' => [
    'name' => 'Professional',
    'price' => 99,
    // ...
    'stripe_price_id' => env('STRIPE_PRICE_PRO'),
    'stripe_lookup_key' => 'pro',
],
```

- `stripe_price_id` → the actual `price_xxx` from your Stripe dashboard. Filled via `.env`.
- `stripe_lookup_key` → the human-readable identifier you must set on the Stripe Price object so the webhook can resolve which plan to apply.

## Adding a new plan

1. **Create the product + price in Stripe Dashboard**
   - Go to *Products → Add product*.
   - Fill name (e.g. "ImporteX Professional").
   - Add a recurring price with currency `EUR` and the desired monthly amount.
   - **Important**: in the price metadata or "Lookup key" field, set `lookup_key = 'pro'` (must match `stripe_lookup_key` in config).
   - Copy the `price_xxx` ID.

2. **Add the plan to `config/subscription.php`** (or to a new env-driven file if you prefer externalization):

   ```php
   'pro' => [
       'name' => 'Professional',
       'price' => 99,
       // ...limits...
       'stripe_price_id' => env('STRIPE_PRICE_PRO'),
       'stripe_lookup_key' => 'pro',
   ],
   ```

3. **Add the env variable** to `.env` and `.env.example`:

   ```bash
   STRIPE_PRICE_PRO=price_1ABC...
   ```

4. **Restart workers** (Horizon / queue) so the new config is picked up.

5. **Verify** with `php artisan config:show subscription.plans.pro` that `stripe_price_id` resolves.

## Webhook resolution order

The webhook handler in [`StripeWebhookController`](../../app/Http/Controllers/StripeWebhookController.php) extracts the plan from a Stripe subscription payload in this order:

1. `price.lookup_key` — preferred (set on the Stripe dashboard).
2. `price.nickname` — legacy alternative.
3. Match `price.id` against `config('subscription.plans.*.stripe_price_id')` — fallback.

If none resolve, the webhook logs a warning and skips the plan update. **The subscription will be active in Stripe but `organizations.plan` will not change** — this is the most common cause of "Stripe says active but our DB says starter" bugs.

## Adding a new webhook event

1. Add the event to `config/cashier.php` under `webhook.events` (or to your override).
2. Implement the corresponding `handle<EventName>(array $payload)` method in `StripeWebhookController`.
3. Always:
   - Wrap in `try/catch` and log with `Log::error()`.
   - Resolve the org via `$this->getUserByStripeId($payload['data']['object']['customer'])`.
   - Respect `$org->isOwner()` — owners should never be downgraded.
   - Update `payment_failed_at` on failures and clear it on success.

## Testing webhook events locally

Use the Stripe CLI to forward events to your local Laravel:

```bash
stripe listen --forward-to http://127.0.0.1:8000/stripe/webhook
stripe trigger customer.subscription.created
stripe trigger invoice.payment_failed
```

The `Cache::lock` 24-hour deduplication means you can re-trigger the same event id without side effects — useful when debugging.