<!DOCTYPE html>
<html lang="{{ locale }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ isEs ? '¡Suscripción reactivada!' : 'Subscription reactivated!' }}</title>
</head>
<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif; background-color: #f5f5f5;">
    <div style="max-width: 600px; margin: 40px auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
        <!-- Header -->
        <div style="background: linear-gradient(135deg, #059669 0%, #047857 100%); padding: 30px; text-align: center;">
            <h1 style="margin: 0; color: #ffffff; font-size: 24px; font-weight: 600;">
                {{ isEs ? '¡Suscripción reactivada!' : 'Subscription reactivated!' }}
            </h1>
        </div>

        <!-- Content -->
        <div style="padding: 30px;">
            <p style="margin: 0 0 20px; color: #374151; font-size: 16px; line-height: 1.6;">
                {{ isEs ? 'Hola' : 'Hi' }} <strong>{{ userName }}</strong>,
            </p>

            <p style="margin: 0 0 20px; color: #374151; font-size: 16px; line-height: 1.6;">
                {{ isEs ? `¡Buenas noticias! Tu suscripción a ${organizationName} ha sido reactivada con éxito.` : `Great news! Your ${organizationName} subscription has been successfully reactivated.` }}
            </p>

            <p style="margin: 0 0 30px; color: #374151; font-size: 16px; line-height: 1.6;">
                {{ isEs ? `Ahora estás disfrutando del plan ${planName} con todas las funcionalidades activas.` : `You're now enjoying the ${planName} plan with all features active.` }}
            </p>

            <!-- CTA Button -->
            <div style="text-align: center; margin: 30px 0;">
                <a href="{{ appUrl }}/dashboard" style="display: inline-block; padding: 12px 30px; background: #059669; color: #ffffff; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 15px;">
                    {{ isEs ? 'Ir al dashboard' : 'Go to dashboard' }}
                </a>
            </div>

            <!-- Info -->
            <div style="background: #f9fafb; border-left: 4px solid #059669; padding: 15px; margin: 20px 0; border-radius: 4px;">
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
