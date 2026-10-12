<?php

return [
    'shared' => [
        'success' => 'Enlace de seguimiento generado correctamente. Compártelo con tu cliente: :url',
        'revoked' => 'Enlace de seguimiento revocado. Tu cliente ya no puede acceder a la página.',
        'regenerated' => 'Token regenerado. El enlace anterior quedó invalidado; comparte el nuevo con tu cliente.',
        'not_trackable_status' => 'El seguimiento se puede compartir desde que el coche entra en proceso (localizado, reservado, comprado, en tránsito…).',
        'mail_subject' => 'Tu coche :brand :model — sigue su proceso de importación',
        'mail_intro' => '¡Hola! Estás recibiendo este correo porque has encargado tu :brand :model del año :year con JJ Import Motors.',
        'mail_body' => 'Hemos abierto una página privada para que sigas el proceso de importación paso a paso. Podrás ver el estado, las inspecciones realizadas y la fecha estimada de entrega.',
        'mail_cta' => 'Seguir mi coche',
        'mail_footer' => 'Si tienes cualquier duda, responde a este correo y te atenderemos personalmente.',
    ],
    'contract' => [
        'created' => 'Contrato generado. Comparte el enlace o el código QR con tu cliente: :url',
        'need_client' => 'Vincula primero un cliente al coche para poder generar el contrato.',
    ],
];
