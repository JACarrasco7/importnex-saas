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
            'users_limit' => 3,
            'description' => 'Para empezar, con pocos coches a la vez',
            // Mismas funciones en todos los planes; cambian los límites y, en
            // Enterprise, la web sin la marca de la plataforma.
            'features' => [
                'public_website' => true,
                'order_portal' => true,
                'ai_own_key' => true,
                'claude_skill' => true,
                'api_access' => true,
                'webhook_notifications' => true,
                'weekly_digest' => true,
                'custom_branding' => false,
                'email_support' => true,
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
            'users_limit' => 10,
            'description' => 'Para equipos con volumen de encargos',
            // Mismas funciones en todos los planes; cambian los límites y, en
            // Enterprise, la web sin la marca de la plataforma.
            'features' => [
                'public_website' => true,
                'order_portal' => true,
                'ai_own_key' => true,
                'claude_skill' => true,
                'api_access' => true,
                'webhook_notifications' => true,
                'weekly_digest' => true,
                'custom_branding' => false,
                'email_support' => true,
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
            'users_limit' => 50,
            'description' => 'Para redes de importación, con tu web sin nuestra marca',
            // Mismas funciones en todos los planes; cambian los límites y, en
            // Enterprise, la web sin la marca de la plataforma.
            'features' => [
                'public_website' => true,
                'order_portal' => true,
                'ai_own_key' => true,
                'claude_skill' => true,
                'api_access' => true,
                'webhook_notifications' => true,
                'weekly_digest' => true,
                'custom_branding' => true,
                'email_support' => true,
            ],
            'stripe_price_id' => env('STRIPE_PRICE_ENTERPRISE'),
            'stripe_price_annual_id' => env('STRIPE_PRICE_ENTERPRISE_ANNUAL'),
            'stripe_lookup_key' => 'enterprise',
        ],
    ],

    'trial_days' => 14,

    // Bloquea la aplicación (salvo planes y facturación) cuando la prueba
    // gratuita termina sin suscripción. false = instalación sin cobro.
    'enforce' => env('SUBSCRIPTION_ENFORCE', true),
    'default_currency' => 'eur',

    // Proration behavior for plan swaps.
    // Options: 'create_prorations' (default, credit applied), 'none' (no proration),
    // 'always_invoice' (invoice immediately).
    'proration_behavior' => env('STRIPE_PRORATION_BEHAVIOR', 'create_prorations'),

    // Grace period (days) after a failed payment before downgrading to 'starter'.
    // Implemented via webhook handler when invoice.payment_failed is received.
    'payment_failed_grace_days' => (int) env('SUBSCRIPTION_GRACE_DAYS', 7),
];
