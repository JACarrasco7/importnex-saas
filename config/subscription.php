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
            'features' => [
                'email_support' => true,
                'marketplace_access' => true,
                'ai_credits' => 10,
                'advanced_analytics' => false,
                'api_access' => false,
                'custom_branding' => false,
                'webhook_notifications' => false,
                'weekly_digest' => false,
            ],
            'stripe_price_id' => env('STRIPE_PRICE_STARTER'),
            'stripe_price_annual_id' => env('STRIPE_PRICE_STARTER_ANNUAL'),
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
            'features' => [
                'email_support' => true,
                'marketplace_access' => true,
                'ai_credits' => 100,
                'advanced_analytics' => true,
                'api_access' => true,
                'custom_branding' => false,
                'webhook_notifications' => true,
                'weekly_digest' => true,
            ],
            'stripe_price_id' => env('STRIPE_PRICE_PRO'),
            'stripe_price_annual_id' => env('STRIPE_PRICE_PRO_ANNUAL'),
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
            'features' => [
                'email_support' => true,
                'marketplace_access' => true,
                'ai_credits' => 1000,
                'advanced_analytics' => true,
                'api_access' => true,
                'custom_branding' => true,
                'webhook_notifications' => true,
                'weekly_digest' => true,
            ],
            'stripe_price_id' => env('STRIPE_PRICE_ENTERPRISE'),
            'stripe_price_annual_id' => env('STRIPE_PRICE_ENTERPRISE_ANNUAL'),
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
