<?php

return [
    'shared' => [
        'success' => 'Tracking link generated successfully. Share it with your client: :url',
        'revoked' => 'Tracking link revoked. Your client can no longer access the page.',
        'regenerated' => 'Token rotated. The previous link stopped working; share the new one with your client.',
        'not_trackable_status' => 'Tracking can be shared as soon as the car enters the process (located, reserved, purchased, in transit…).',
        'mail_subject' => 'Your :brand :model — follow its import process',
        'mail_intro' => 'Hi! You are getting this email because you ordered your :year :brand :model with JJ Import Motors.',
        'mail_body' => 'We opened a private page so you can follow the import process step by step. You will see the status, completed inspections and the estimated delivery date.',
        'mail_cta' => 'Track my car',
        'mail_footer' => 'If you have any question, reply to this email and we will get back to you personally.',
    ],
    'contract' => [
        'created' => 'Contract generated. Share the link or QR code with your client: :url',
        'need_client' => 'Link a client to the car first to generate the contract.',
    ],
];
