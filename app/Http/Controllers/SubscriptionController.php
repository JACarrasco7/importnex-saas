<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Cashier\Exceptions\PaymentActionRequired;
use Laravel\Cashier\Exceptions\PaymentFailure;

class SubscriptionController extends Controller
{
    public function index(): Response
    {
        $plans = config('subscription.plans');
        $org = auth()->user()->organization;
        $subscription = $org->subscription('main');

        // payment_failed_at is set by the webhook and rendered as a banner.
        // It's cleared on resume/create so the banner disappears once fixed.
        $paymentFailed = $org->payment_failed_at !== null;

        return Inertia::render('Subscriptions/Index', [
            'plans' => $plans,
            'currentPlan' => $org->plan ?? 'starter',
            'isOwner' => $org->isOwner(),
            'subscription' => $subscription ? [
                'status' => $subscription->stripe_status,
                'ends_at' => $subscription->ends_at?->format('Y-m-d'),
                'trial_ends_at' => $subscription->trial_ends_at?->format('Y-m-d'),
            ] : null,
            'on_trial' => $org->onTrial('main'),
            'trial_ends_at' => $org->trialEndsAt('main')?->format('Y-m-d'),
            'paymentFailed' => $paymentFailed,
        ]);
    }

    public function show($plan): Response
    {
        $plans = config('subscription.plans');

        if (! isset($plans[$plan])) {
            abort(404);
        }

        return Inertia::render('Subscriptions/Show', [
            'plan' => $plan,
            'planData' => $plans[$plan],
            'isOwner' => auth()->user()->organization?->isOwner() ?? false,
        ]);
    }

    public function create(Request $request, $plan): RedirectResponse
    {
        $plans = config('subscription.plans');

        if (! isset($plans[$plan])) {
            return back()->with('error', 'Plan not found');
        }

        $org = $request->user()->organization;

        if ($org->isOwner()) {
            return back()->with('error', 'Tu cuenta tiene acceso ilimitado vitalicio.');
        }

        $existing = $org->subscription('main');
        if ($existing && $existing->active() && $org->plan === $plan) {
            return redirect()->route('subscriptions.index')
                ->with('success', 'Ya tienes este plan activo.');
        }

        $billingCycle = $request->input('billing_cycle', 'monthly');
        if (! in_array($billingCycle, ['monthly', 'annual'], true)) {
            $billingCycle = 'monthly';
        }
        $priceId = $billingCycle === 'annual' && ! empty($plans[$plan]['stripe_price_annual_id'])
            ? $plans[$plan]['stripe_price_annual_id']
            : $plans[$plan]['stripe_price_id'] ?? $plan;

        $paymentMethodId = $request->paymentMethodId;

        if (! $paymentMethodId) {
            try {
                $paymentMethod = $org->defaultPaymentMethod();
                if ($paymentMethod) {
                    $paymentMethodId = $paymentMethod->id;
                }
            } catch (\Throwable $e) {
                // No payment method available — handled below
            }
        }

        try {
            $org->newSubscription('main', $priceId)
                ->trialDays(config('subscription.trial_days'))
                ->create($paymentMethodId);

            $org->update([
                'plan' => $plan,
                'subscribed_at' => $org->subscribed_at ?? now(),
            ]);
        } catch (PaymentActionRequired $e) {
            return redirect()->route('cashier.payment', [$e->payment->id])
                ->with('warning', 'Se requiere acción adicional para confirmar el pago.');
        } catch (PaymentFailure $e) {
            return back()->with('error', 'El pago fue rechazado. Revisa el método de pago.');
        } catch (\Throwable $e) {
            Log::error('Subscription create failed', [
                'organization_id' => $org->id,
                'plan' => $plan,
                'billing_cycle' => $billingCycle,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'No se pudo crear la suscripción. Inténtalo de nuevo o contacta soporte.');
        }

        return redirect()->route('subscriptions.index')
            ->with('success', 'Suscripción creada correctamente.');
    }

    public function swap(Request $request, $newPlan): RedirectResponse
    {
        $plans = config('subscription.plans');

        if (! isset($plans[$newPlan])) {
            return back()->with('error', 'Plan not found');
        }

        $org = $request->user()->organization;

        if ($org->isOwner()) {
            return back()->with('error', 'Tu cuenta tiene acceso ilimitado vitalicio.');
        }

        $subscription = $org->subscription('main');

        if (! $subscription) {
            return redirect()->route('subscriptions.create', $newPlan);
        }

        if ($org->plan === $newPlan) {
            return redirect()->route('subscriptions.index')
                ->with('success', 'Ya estás en este plan.');
        }

        // Downgrade to 'starter' requires cancellation: Cashier cannot swap to
        // a non-Stripe-managed free plan without explicit cancel + downgrade flow.
        if ($newPlan === 'starter') {
            return redirect()->route('subscriptions.cancel');
        }

        $billingCycle = $request->input('billing_cycle', 'monthly');
        if (! in_array($billingCycle, ['monthly', 'annual'], true)) {
            $billingCycle = 'monthly';
        }
        $priceId = $billingCycle === 'annual' && ! empty($plans[$newPlan]['stripe_price_annual_id'])
            ? $plans[$newPlan]['stripe_price_annual_id']
            : $plans[$newPlan]['stripe_price_id'] ?? $newPlan;

        try {
            $subscription->swap($priceId);
            $org->update(['plan' => $newPlan]);
        } catch (\Throwable $e) {
            Log::error('Subscription swap failed', [
                'organization_id' => $org->id,
                'new_plan' => $newPlan,
                'billing_cycle' => $billingCycle,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'No se pudo cambiar el plan. Inténtalo de nuevo.');
        }

        return redirect()->route('subscriptions.index')
            ->with('success', 'Plan actualizado correctamente.');
    }

    public function cancelPage(Request $request): Response
    {
        $org = $request->user()->organization;

        return Inertia::render('Subscriptions/Cancel', [
            'subscription' => $org ? $org->subscription('main') : null,
        ]);
    }

    public function cancel(Request $request): RedirectResponse
    {
        $org = $request->user()->organization;

        if ($org->isOwner()) {
            return back()->with('error', 'No tienes ninguna suscripción que cancelar.');
        }

        $subscription = $org->subscription('main');

        if (! $subscription) {
            return back()->with('error', 'No tienes una suscripción activa.');
        }

        try {
            $subscription->cancel();
        } catch (\Throwable $e) {
            Log::error('Subscription cancel failed', [
                'organization_id' => $org->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'No se pudo cancelar la suscripción.');
        }

        return redirect()->route('subscriptions.index')
            ->with('success', 'Suscripción cancelada. Mantendrás el acceso hasta fin de periodo.');
    }

    public function resume(Request $request): RedirectResponse
    {
        $org = $request->user()->organization;

        if ($org->isOwner()) {
            return back()->with('error', 'No tienes ninguna suscripción para reactivar.');
        }

        $subscription = $org->subscription('main');

        if (! $subscription) {
            return back()->with('error', 'No tienes una suscripción para reactivar.');
        }

        try {
            $subscription->resume();
        } catch (\Throwable $e) {
            Log::error('Subscription resume failed', [
                'organization_id' => $org->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'No se pudo reactivar la suscripción.');
        }

        return redirect()->route('subscriptions.index')
            ->with('success', 'Suscripción reactivada correctamente.');
    }
}
