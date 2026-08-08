<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class PublicPricingController extends Controller
{
    /**
     * Render the public pricing page.
     * No authentication required.
     * Plans are fetched from config/subscription.php with discounts applied.
     */
    public function __invoke(Request $request): Response
    {
        // Cache for 1 hour to reduce config reading overhead
        $plans = Cache::remember('public.pricing.plans', 3600, function () {
            return $this->getPlansWithPricing();
        });

        // Get billing cycle preference from query param or default to monthly
        $billingCycle = $request->query('billing_cycle', 'monthly');

        return Inertia::render('Public/Pricing', [
            'plans' => $plans,
            'billing_cycle' => $billingCycle,
            'annual_discount' => config('subscription.annual_discount', 0.2),
            'trial_days' => config('subscription.trial_days', 14),
        ]);
    }

    /**
     * Get plans with computed pricing for monthly/annual cycles.
     */
    protected function getPlansWithPricing(): array
    {
        $plans = config('subscription.plans', []);
        $annualDiscount = config('subscription.annual_discount', 0.2);

        return collect($plans)->map(function ($plan) use ($annualDiscount) {
            $monthlyPrice = $plan['monthly_price'] ?? 0;
            $annualPrice = $monthlyPrice * 12 * (1 - $annualDiscount);

            return array_merge($plan, [
                'monthly_price' => $monthlyPrice,
                'annual_price' => round($annualPrice, 2),
                'annual_monthly_price' => round($annualPrice / 12, 2),
                'annual_savings' => round($monthlyPrice * 12 - $annualPrice, 2),
            ]);
        })->toArray();
    }
}