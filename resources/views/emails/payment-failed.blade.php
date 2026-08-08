<!DOCTYPE html>
<html lang="{{ locale }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ isEs ? 'Pago fallado - Tu suscripción necesita atención' : 'Payment failed - Your subscription needs attention' }}</title>
</head>
<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif; background-color: #f5f5f5;">
    <div style="max-width: 600px; margin: 40px auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
        <!-- Header -->
<div style="background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%); padding: 30px; text-align: center;">
            <h1 style="margin: 0; color: #ffffff; font-size: 24px; font-weight: 600;">
                {{ isEs ? 'Pago fallado' : 'Payment failed' }}
            </h1>
        </div>

        <!-- Content -->
        <div style="padding: 30px;">
            <p style="margin: 0 0 20px; color: #374151; font-size: 16px; line-height: 1.6;">
                {{ isEs ? 'Hola' : 'Hi' }} <strong>{{ userName }}</strong>,
            </p>

            <p style="margin: 0 0 20px; color: #374151; font-size: 16px; line-height: 1.6;">
                {{ isEs ? `No hemos podido procesar el pago de tu suscripción a ${organizationName}.` : `We couldn't process the payment for your ${organizationName} subscription.` }}
            </p>

            <p style="margin: 0 0 30px; color: #374151; font-size: 16px; line-height: 1.6;">
                {{ isEs ? `Tienes ${graceDays} días para actualizar tu método de pago antes de que tu suscripción se degrade al plan Starter.` : `You have ${graceDays} days to update your payment method before your subscription is downgraded to the Starter plan.` }}
            </p>

            <!-- CTA Button -->
            <div style="text-align: center; margin: 30px 0;">
                <a href="{{ appUrl }}/billing" style="display: inline-block; padding: 12px 30px; background: #dc2626; color: #ffffff; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 15px;">
                    {{ isEs ? 'Actualizar método de pago' : 'Update payment method' }}
                </a>
            </div>

            <!-- Info -->
            <div style="background: #f9fafb; border-left: 4px solid #dc2626; padding: 15px; margin: 20px 0; border-radius: 4px;">
                <p style="margin: 0; color: #6b7280; font-size: 14px;">
                    <strong>{{ isEs ? '¿Necesitas ayuda?' : 'Need help?' }}</strong>
                    {{ isEs ? 'Contacta con nuestro equipo de soporte.' : 'Contact our support team.' }}
                </p>
            </div>
        </div>

        <!-- Footer -->
        <div style="background: #f9fafb; padding: 20px 30px; text-align: center; border-top: 1px solid #e5e7eb;">
            <p style="margin: 0; color: #6b7280; font-size: 13px;">
                {{ organizationName }} — JJ Import Motors
            </p>
        </div>
    </div>
</body>
</html>
