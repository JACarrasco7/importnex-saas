<?php

return [
    'mail' => [
        'greeting' => 'Hola :name,',
        'signature' => 'Un saludo,'."\n".'El equipo de JJ Import Motors',
        'footer' => 'Este es tu enlace privado: no lo compartas. Si tienes dudas, responde a este correo o escríbenos por WhatsApp.',
        'button_portal' => 'Abrir mi encargo',

        'contract_issued_subject' => 'Tu contrato de servicio para el :vehicle',
        'contract_issued_heading' => 'Tu contrato está listo para firmar',
        'contract_issued_line1' => 'Hemos preparado el contrato de servicio de tu encargo :reference (:vehicle).',
        'contract_issued_line2' => 'Puedes leerlo y firmarlo desde el móvil en 2 minutos. Al firmar te enviaremos un código por email para confirmar que eres tú.',
        'contract_issued_line3' => 'El enlace para firmar es válido hasta el :date.',
        'contract_issued_button' => 'Revisar y firmar',

        'contract_reminder_subject' => 'Recordatorio: tu contrato está pendiente de firma',
        'contract_reminder_heading' => 'Tu contrato sigue pendiente',
        'contract_reminder_line1' => 'Aún no has firmado el contrato de tu encargo :reference (:vehicle). Caduca el :date.',
        'contract_reminder_line2' => 'Si tienes alguna duda, pulsa «Tengo dudas» en la página del contrato y te llamamos.',

        'contract_signed_subject' => 'Contrato firmado — encargo :reference',
        'contract_signed_heading' => '¡Contrato firmado!',
        'contract_signed_line1' => 'Hemos recibido tu firma del contrato de servicio para el :vehicle. Te adjuntamos una copia.',
        'contract_signed_line2' => 'Siguiente paso: abonar la señal (:amount €). En tu página tienes los datos de pago.',
        'contract_signed_line2_nofee' => 'Ya estamos con el siguiente paso: la inspección del coche.',
        'contract_signed_line3' => 'Huella del documento firmado (SHA-256): :hash',

        'phase_changed_subject' => 'Novedades de tu :vehicle: :state',
        'phase_changed_heading' => ':state',
        'phase_changed_line1' => 'Tu encargo :reference ha avanzado de fase.',

        'payment_subject' => 'Pago recibido — encargo :reference',
        'payment_heading' => 'Hemos recibido tu pago',
        'payment_line1' => 'Confirmamos el pago de :amount € (:concept) por :method.',
        'payment_line2' => 'Gracias. Puedes ver el estado de todos tus pagos en tu página.',

        'approval_subject' => 'Necesitamos tu respuesta — :title',
        'approval_heading' => 'Necesitamos tu OK',
        'approval_line1' => ':title',
        'approval_amount' => 'Importe: :amount €',
        'approval_line2' => 'Te lo explicamos con fotos en tu página. Puedes responder con un clic antes del :date.',
        'approval_button' => 'Revisar y responder',

        'message_subject' => 'Nuevo mensaje sobre tu :vehicle',
        'message_heading' => 'Te hemos escrito',
        'message_button' => 'Ver y responder',

        'otp_subject' => 'Tu código de verificación: :code',
        'otp_heading' => 'Tu código de verificación',
        'otp_line1' => 'Usa este código para :purpose en tu encargo :reference:',
        'otp_line2' => 'Caduca en :minutes minutos. Si no lo has pedido tú, ignora este correo.',
        'otp_purpose_sign_contract' => 'firmar el contrato',
        'otp_purpose_approve' => 'confirmar tu respuesta',
        'otp_purpose_cancel' => 'confirmar la cancelación',

        'recover_subject' => 'Tu enlace de seguimiento de JJ Import Motors',
        'recover_heading' => 'Aquí tienes tu enlace',
        'recover_line1' => 'Has pedido recuperar el acceso a tu encargo :reference (:vehicle).',

        // Equipo
        'team_contract_signed_subject' => '✍️ :client ha firmado el contrato (:reference)',
        'team_contract_signed_line' => ':client ha firmado el contrato del :vehicle. Se han generado los pagos de honorarios.',
        'team_contract_rejected_subject' => '⚠️ :client tiene dudas sobre el contrato (:reference)',
        'team_contract_rejected_line' => 'Motivo indicado por el cliente: :reason',
        'team_contract_voided_subject' => 'Contrato anulado — :reference',
        'team_contract_voided_line' => 'El contrato de :client (:vehicle) se ha anulado: :reason. Reemítelo desde el encargo.',
        'team_approval_resolved_subject' => ':client ha respondido: :title (:reference)',
        'team_approval_expired_subject' => 'Sin respuesta: :title (:reference)',
        'team_message_subject' => '💬 Nuevo mensaje de :client (:reference)',
        'team_cancellation_subject' => '🛑 :client pide cancelar el encargo :reference',
        'team_cancellation_line' => 'Motivo: :reason. Revisa el encargo y contacta con el cliente.',
        'team_button' => 'Abrir encargo',
    ],
];
