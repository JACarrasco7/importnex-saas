<?php

return [
    'plans' => [
        'starter' => [
            'name' => 'Starter',
            'price' => 29,
            'currency' => 'eur',
            'period' => 'month',
            'cars_limit' => 10,
            'clients_limit' => 50,
            'contacts_limit' => 25,
            'description' => 'Basic plan for small workshops',
            // Override these in .env with real Stripe price IDs once products
            // are created in Stripe dashboard. Also set lookup_key = 'starter'
            // on the price so webhooks resolve correctly.
            'stripe_price_id' => env('STRIPE_PRICE_STARTER'),
            'stripe_lookup_key' => 'starter',
        ],
        'pro' => [
            'name' => 'Professional',
            'price' => 99,
            'currency' => 'eur',
            'period' => 'month',
            'cars_limit' => 100,
            'clients_limit' => 500,
            'contacts_limit' => 250,
            'description' => 'Professional plan for medium workshops',
            'stripe_price_id' => env('STRIPE_PRICE_PRO'),
            'stripe_lookup_key' => 'pro',
        ],
        'enterprise' => [
            'name' => 'Enterprise',
            'price' => 299,
            'currency' => 'eur',
            'period' => 'month',
            'cars_limit' => 1000,
            'clients_limit' => 5000,
            'contacts_limit' => 2500,
            'description' => 'Enterprise plan for large networks',
            'stripe_price_id' => env('STRIPE_PRICE_ENTERPRISE'),
            'stripe_lookup_key' => 'enterprise',
        ],
    ],

    'trial_days' => 14,
    'default_currency' => 'eur',

    // Proration behavior for plan swaps.
    // Options: 'create_prorations' (default, credit applied), 'none' (no proration),
    // 'always_invoice' (invoice immediately).
    'proration_behavior' => env('STRIPE_PRORATION_BEHAVIOR', 'create_prorations'),

    // Grace period (days) after a failed payment before downgrading to 'starter'.
    // Implemented via webhook handler when invoice.payment_failed is received.
    'payment_failed_grace_days' => (int) env('SUBSCRIPTION_GRACE_DAYS', 7),
];
