# Session E — Billing UX + Dunning (2026-08-07)

**Fecha:** 2026-08-07
**Estado:** Iniciando
**Items completados:** 1/6 (DunningBanner.vue creado)
**Items pendientes:** 5/6 (~14h)

---

## ✅ YA HECHOS

### 5.3 Banner dunning en dashboard (parcial) ✅
**Commit:** `9eac1b1`
**Archivos:**
- `resources/js/Components/DunningBanner.vue` (creado)

**Pendiente:** Integrar en Dashboard.vue + HandleInertiaRequests (props subscription)

---

## ⏳ PENDIENTES

### 5.1 Tabla comparativa de planes (Features matrix) (~3h)

**Objetivo:** Tabla visual con check/cross para features por plan.

**Backend (`app/Http/Controllers/SubscriptionController.php`):**
```php
public function plans(): Response
{
    $plans = collect([
        'basic' => [...],
        'pro' => [...],
        'enterprise' => [...],
    ]);

    return Inertia::render('Subscriptions/Index', [
        'plans' => $plans,
        'billingPeriod' => 'monthly',
    ]);
}
```

**Frontend (`Subscriptions/Index.vue`):**
```vue
<table class="w-full">
    <thead>
        <tr>
            <th class="text-left">Feature</th>
            <th v-for="plan in plans" :key="plan.id">{{ plan.name }}</th>
        </tr>
    </thead>
    <tbody>
        <tr v-for="feature in features" :key="feature.id">
            <td>{{ feature.name }}</td>
            <td v-for="plan in plans" :key="plan.id">
                <CheckIcon v-if="feature.plans[plan.id]" class="text-green-500" />
                <XMarkIcon v-else class="text-gray-300" />
            </td>
        </tr>
    </tbody>
</table>
```

---

### 5.2 Toggle mensual/anual con % descuento (~2h)

**Frontend:**
```vue
<div class="flex items-center justify-center gap-3">
    <span>Mensual</span>
    <Switch v-model="billingPeriod" :value="billingPeriod"
        @update:model-value="updateBillingPeriod"
    />
    <span>Anual <span class="badge">-20%</span></span>
</div>
```

**Reducer en plan selection:**
```vue
<PricingCard
    v-for="plan in plans"
    :key="plan.id"
    :plan="plan"
    :billing-period="billingPeriod"
    :price="billingPeriod === 'yearly' ? plan.price_yearly : plan.price_monthly"
    :discount="billingPeriod === 'yearly' ? '20%' : ''"
/>
```

---

### 5.3 Banner dunning en Dashboard (integrar) (~2h)

**Frontend (`Dashboard.vue`):**
```vue
<template>
    <AuthenticatedLayout>
        <template #header>
            <DunningBanner
                :payment-failed="$page.props.subscription?.payment_failed"
                :payment-failed-at="$page.props.subscription?.payment_failed_at"
            />
        </template>
        ...
    </AuthenticatedLayout>
</template>
```

**Backend (`HandleInertiaRequests.php`):**
```php
public function share(Request $request): array
{
    return [
        ...parent::share($request),
        'subscription' => fn() => $request->user()?->subscription
            ? [
                'payment_failed' => $request->user()->subscription->payment_failed,
                'payment_failed_at' => $request->user()->subscription->payment_failed_at,
                'on_grace_period' => $request->user()->subscription->onGracePeriod(),
            ]
            : null,
    ];
}
```

---

### 5.4 Emails transaccionales en español (~4h)

**Templates a crear:**
- `resources/views/emails/billing/trial_ending.blade.php`
- `resources/views/emails/billing/payment_failed.blade.php`
- `resources/views/emails/billing/reactivated.blade.php`

**Mailable classes:**
- `app/Mail/TrialEndingMail.php`
- `app/Mail/PaymentFailedMail.php`
- `app/Mail/ReactivatedMail.php`

**Listeners:**
- `app/Listeners/SendTrialEndingEmail.php`
- `app/Listeners/SendPaymentFailedEmail.php`
- `app/Listeners/SendReactivatedEmail.php`

**Triggered:**
- `Cashier subscription.trial_ending` → TrialEndingMail
- `Stripe payment_intent.payment_failed` → PaymentFailedMail
- `Stripe invoice.payment_succeeded` → ReactivatedMail

---

### 5.5 Página `/billing/cancel` honesta (~2h)

**Frontend (`Subscriptions/Cancel.vue`):**
```vue
<template>
    <PublicLayout>
        <div class="mx-auto max-w-3xl">
            <h1>¿Qué pasa si cancelas?</h1>

            <section>
                <h2>Inmediatamente</h2>
                <ul>
                    <li>Tu suscripción queda en grace period de 7 días</li>
                    <li>Sigues con acceso a todas las funciones</li>
                    <li>NO se realizan más cargos</li>
                </ul>
            </section>

            <section>
                <h2>Después de 7 días</h2>
                <ul>
                    <li>Vuelves a plan Free (limitado)</li>
                    <li>Conservas tus datos por 30 días</li>
                    <li>Puedes reactivarte cuando quieras</li>
                </ul>
            </section>

            <div class="actions">
                <Link :href="route('billing.portal')">Mantener suscripción</Link>
                <button @click="confirmCancel">Confirmar cancelación</button>
            </div>
        </div>
    </PublicLayout>
</template>
```

---

### 5.6 `UpgradePrompt` contextual (~3h)

**Componente:**
```vue
<!-- resources/js/Components/UpgradePrompt.vue -->
<template>
    <div v-if="show" class="upgrade-prompt">
        <h3>{{ t('upgrade.reached_limit', 'Has alcanzado el límite de tu plan') }}</h3>
        <p>{{ feature }}: {{ limit }} / {{ limit }}</p>
        <Link :href="route('subscriptions.index')">Mejorar plan</Link>
        <button @click="dismiss">Más tarde</button>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';

const props = defineProps({
    feature: { type: String, required: true },
    limit: { type: Number, required: true },
    threshold: { type: Number, default: 0.9 },
});

const show = ref(false);
const dismissKey = `upgrade-${props.feature}-dismissed`;

onMounted(() => {
    const dismissed = localStorage.getItem(dismissKey);
    if (dismissed && Date.now() - Number(dismissed) < 7 * 24 * 60 * 60 * 1000) {
        return;
    }
    if (props.limit >= props.threshold * 100) {
        show.value = true;
    }
});

const dismiss = () => {
    localStorage.setItem(dismissKey, Date.now().toString());
    show.value = false;
};
</script>
```

**Uso en Cars/Index.vue:**
```vue
<UpgradePrompt feature="cars" :limit="cars.total" />
<UpgradePrompt feature="clients" :limit="clients.total" />
```

---

## 📊 Tiempo estimado

| Item | Esfuerzo | Estado |
|---|---|---|
| 5.1 Tabla comparativa | 3h | ⏳ Pendiente |
| 5.2 Toggle anual | 2h | ⏳ Pendiente |
| 5.3 Banner dunning (integrar) | 2h | ⏳ Pendiente |
| 5.4 Emails ES | 4h | ⏳ Pendiente |
| 5.5 Cancel honest page | 2h | ⏳ Pendiente |
| 5.6 UpgradePrompt | 3h | ⏳ Pendiente |
| **TOTAL** | **~16h** | **1/6 (parcial)** |

---

## 🎯 Orden de ejecución

1. **5.3 Banner dunning integrar** (2h) — más impacto, ya tienes componente
2. **5.1 Tabla comparativa** (3h) — alta conversion
3. **5.2 Toggle anual** (2h) — combinado con 5.1
4. **5.5 Cancel honest** (2h) — reducir churn
5. **5.4 Emails ES** (4h) — dunning suave
6. **5.6 UpgradePrompt** (3h) — contextual

---

## 📝 Commits esperados

```bash
git commit -m "feat(billing 5.3): integrar DunningBanner en Dashboard"
git commit -m "feat(billing 5.1+5.2): tabla comparativa + toggle anual"
git commit -m "feat(billing 5.5): página /subscriptions/cancel honesta"
git commit -m "feat(billing 5.4): emails transaccionales en español"
git commit -m "feat(billing 5.6): UpgradePrompt contextual con localStorage"
```