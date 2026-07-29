<?php

return [
    'plans' => [
        'starter' => [
            'name' => 'Starter',
            'price' => 29,
            'currency' => 'eur',
            'period' => 'month',
            'car_limit' => 10,
            'client_limit' => 50,
            'contact_limit' => 25,
            'description' => 'Basic plan for small workshops',
        ],
        'pro' => [
            'name' => 'Professional',
            'price' => 99,
            'currency' => 'eur',
            'period' => 'month',
            'car_limit' => 100,
            'client_limit' => 500,
            'contact_limit' => 250,
            'description' => 'Professional plan for medium workshops',
        ],
        'enterprise' => [
            'name' => 'Enterprise',
            'price' => 299,
            'currency' => 'eur',
            'period' => 'month',
            'car_limit' => 1000,
            'client_limit' => 5000,
            'contact_limit' => 2500,
            'description' => 'Enterprise plan for large networks',
        ],
    ],

    'trial_days' => 14,
    'default_currency' => 'eur',
];
