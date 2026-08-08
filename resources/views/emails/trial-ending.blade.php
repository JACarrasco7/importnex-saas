<!DOCTYPE html>
<html lang="{{ locale }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ isEs ? 'Tu prueba gratuita termina pronto' : 'Your free trial is ending soon' }}</title>
</head>
<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif; background-color: #f5f5f5;">
    <div style="max-width: 600px; margin: 40px auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
        <!-- Header -->
        <div style="background: linear-gradient(135deg, #1A306D 0%, #0F172A 100%); padding: 30px; text-align: center;">
            <h1 style="margin: 0; color: #ffffff; font-size: 24px; font-weight: 600;">
                {{ isEs ? 'Tu prueba gratuita termina pronto' : 'Your free trial is ending soon' }}
            </h1>
        </div>

        <!-- Content -->
        <div style="padding: 30px;">
            <p style="margin: 0 0 20px; color: #374151; font-size: 16px; line-height: 1.6;">
                {{ isEs ? 'Hola' : 'Hi' }} <strong>{{ userName }}</strong>,
            </p>

            <p style="margin: 0 0 20px; color: #374151; font-size: 16px; line-height: 1.6;">
                {{ isEs ? `Tu prueba gratuita de ${organizationName} termina el ${trialEndsAt}.` : `Your ${organizationName} free trial ends on ${trialEndsAt}.` }}
            </p>

            <p style="margin: 0 0 30px; color: #374151; font-size: 16px; line-height: 1.6;">
                {{ isEs ? 'Para seguir disfrutando de todas las funcionalidades, suscríbete a uno de nuestros planes.' : 'To continue enjoying all features, subscribe to one of our plans.' }}
            </p>

            <!-- CTA Button -->
            <div style="text-align: center; margin: 30px 0;">
                <a href="{{ appUrl }}/subscriptions" style="display: inline-block; padding: 12px 30px; background: #1A306D; color: #ffffff; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 15px;">
                    {{ isEs ? 'Ver planes' : 'View plans' }}
                </a>
            </div>

            <!-- Info -->
            <div style="background: #f9fafb; border-left: 4px solid #1A306D; padding: 15px; margin: 20px 0; border-radius: 4px;">
                <p style="margin: 0; color: #6b7280; font-size: 14px;">
                    <strong>{{ isEs ? '¿Tienes preguntas?' : 'Have questions?' }}</strong>
                    {{ isEs ? 'Nuestro equipo está aquí para ayudarte.' : 'Our team is here to help.' }}
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
