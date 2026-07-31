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
        ],
    ],

    'trial_days' => 14,
    'default_currency' => 'eur',
];
