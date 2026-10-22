<?php

/*
 * Encargos de importación: plazos, umbrales y canales.
 * Todo configurable por .env para ajustar sin tocar código.
 */

return [

    // Días que un contrato enviado sigue siendo firmable.
    'contract_valid_days' => (int) env('ORDERS_CONTRACT_VALID_DAYS', 7),

    // Recordatorios de firma: horas desde el envío (1º) y entre recordatorios.
    'contract_first_reminder_hours' => (int) env('ORDERS_CONTRACT_FIRST_REMINDER_HOURS', 24),
    'contract_reminder_every_hours' => (int) env('ORDERS_CONTRACT_REMINDER_EVERY_HOURS', 48),
    'contract_max_reminders' => (int) env('ORDERS_CONTRACT_MAX_REMINDERS', 2),

    // Código de verificación (OTP).
    'otp_ttl_minutes' => (int) env('ORDERS_OTP_TTL_MINUTES', 10),
    'otp_max_attempts' => (int) env('ORDERS_OTP_MAX_ATTEMPTS', 3),
    'otp_max_sends_per_hour' => (int) env('ORDERS_OTP_MAX_SENDS_PER_HOUR', 5),

    // Aprobaciones de costes: a partir de este importe (€) se pide código.
    'otp_amount_threshold' => (float) env('ORDERS_OTP_AMOUNT_THRESHOLD', 300),
    'approval_due_hours' => (int) env('ORDERS_APPROVAL_DUE_HOURS', 48),

    // Días durante los que el portal sigue accesible tras entregar o cancelar.
    'portal_days_after_close' => (int) env('ORDERS_PORTAL_DAYS_AFTER_CLOSE', 90),

    // Contacto que ve el cliente en el portal (WhatsApp y teléfono).
    'contact_phone' => env('ORDERS_CONTACT_PHONE', '+34 675 70 14 39'),
    'contact_email' => env('ORDERS_CONTACT_EMAIL', 'jjimportmotors@gmail.com'),
    'contact_schedule' => env('ORDERS_CONTACT_SCHEDULE', 'Lun-Vie 9:00–19:00 · Sáb 10:00–13:00'),

    // Email del equipo para avisos (firma, mensajes, aprobaciones). Si está
    // vacío se usa el email de los owners de la organización.
    'team_email' => env('ORDERS_TEAM_EMAIL'),

    // Tamaño máximo de foto/documento subido al encargo (KB).
    'media_max_kb' => (int) env('ORDERS_MEDIA_MAX_KB', 15360),
];
