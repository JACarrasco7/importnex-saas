<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SubscriptionController extends Controller
{
    public function index(): Response
    {
        $plans = config('subscription.plans');
        $org = auth()->user()->organization;
        $subscription = $org->subscription('main');

        return Inertia::render('Subscriptions/Index', [
            'plans' => $plans,
            'currentPlan' => $org->plan ?? 'starter',
            'subscription' => $subscription ? [
                'status' => $subscription->stripe_status,
                'ends_at' => $subscription->ends_at?->format('Y-m-d'),
                'trial_ends_at' => $subscription->trial_ends_at?->format('Y-m-d'),
            ] : null,
            'on_trial' => $org->onTrial('main'),
            'trial_ends_at' => $org->trialEndsAt('main')?->format('Y-m-d'),
        ]);
    }

    public function show($plan): Response
    {
        $plans = config('subscription.plans');

        if (!isset($plans[$plan])) {
            abort(404);
        }

        return Inertia::render('Subscriptions/Show', [
            'plan' => $plan,
            'planData' => $plans[$plan],
        ]);
    }

    public function create(Request $request, $plan): RedirectResponse
    {
        $plans = config('subscription.plans');

        if (!isset($plans[$plan])) {
            return back()->with('error', 'Plan not found');
        }

        $request->user()->organization->newSubscription('main', $plan)
            ->trialDays(config('subscription.trial_days'))
            ->create($request->paymentMethodId);

        $request->user()->organization->update(['plan' => $plan]);

        return redirect()->route('subscriptions.index')
            ->with('success', 'Subscription created successfully');
    }

    public function swap(Request $request, $newPlan): RedirectResponse
    {
        $plans = config('subscription.plans');

        if (!isset($plans[$newPlan])) {
            return back()->with('error', 'Plan not found');
        }

        $request->user()->organization->subscription('main')
            ->swap($newPlan);

        $request->user()->organization->update(['plan' => $newPlan]);

        return redirect()->route('subscriptions.index')
            ->with('success', 'Plan updated successfully');
    }

    public function cancel(Request $request): RedirectResponse
    {
        $request->user()->organization->subscription('main')->cancel();

        return redirect()->route('subscriptions.index')
            ->with('success', 'Subscription cancelled. Will continue until period end.');
    }

    public function resume(Request $request): RedirectResponse
    {
        $request->user()->organization->subscription('main')->resume();

        return redirect()->route('subscriptions.index')
            ->with('success', 'Subscription reactivated successfully');
    }
}
