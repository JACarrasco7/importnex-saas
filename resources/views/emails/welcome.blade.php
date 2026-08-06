@php
    $isEs = ($locale ?? 'es') === 'es';
@endphp
<!DOCTYPE html>
<html lang="{{ $isEs ? 'es' : 'en' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin:0;padding:0;background-color:#f3f4f6;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;">
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color:#f3f4f6;padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="600" style="max-width:600px;background-color:#ffffff;border-radius:12px;overflow:hidden;">
                    <!-- Header brand -->
                    <tr>
                        <td style="background:linear-gradient(135deg,#1A306D,#0f1d44);padding:32px 24px;text-align:center;">
                            <h1 style="margin:0;color:#ffffff;font-size:24px;font-weight:700;letter-spacing:-0.5px;">JJ Import Motors</h1>
                        </td>
                    </tr>
                    <!-- Body -->
                    <tr>
                        <td style="padding:40px 32px;color:#1f2937;font-size:16px;line-height:1.6;">
                            <p style="margin:0 0 16px;">
                                {{ $isEs ? 'Hola' : 'Hi' }} <strong>{{ $userName }}</strong>,
                            </p>
                            <p style="margin:0 0 16px;">
                                @if($isEs)
                                    Bienvenido a <strong>{{ $organizationName }}</strong> en JJ Import Motors. Estamos encantados de tenerte a bordo.
                                @else
                                    Welcome to <strong>{{ $organizationName }}</strong> on JJ Import Motors. We're thrilled to have you on board.
                                @endif
                            </p>
                            <p style="margin:0 0 16px;">
                                @if($isEs)
                                    Tienes <strong>14 días</strong> para probar todas las funciones. Empieza por importar tu primer vehículo o crear uno manualmente.
                                @else
                                    You have <strong>14 days</strong> to try all features. Start by importing your first vehicle or creating one manually.
                                @endif
                            </p>
                            <!-- CTA -->
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin:32px 0;">
                                <tr>
                                    <td align="center">
                                        <a href="{{ $appUrl }}/onboarding" style="display:inline-block;background-color:#1A306D;color:#ffffff;padding:14px 28px;border-radius:8px;text-decoration:none;font-weight:600;">
                                            {{ $isEs ? 'Comenzar onboarding' : 'Start onboarding' }}
                                        </a>
                                    </td>
                                </tr>
                            </table>
                            <p style="margin:32px 0 0;color:#6b7280;font-size:14px;">
                                @if($isEs)
                                    Si tienes alguna pregunta, responde a este email y te ayudamos.
                                @else
                                    If you have any questions, reply to this email and we'll help.
                                @endif
                            </p>
                        </td>
                    </tr>
                    <!-- Footer -->
                    <tr>
                        <td style="background-color:#f9fafb;padding:24px;text-align:center;color:#9ca3af;font-size:12px;">
                            © {{ date('Y') }} JJ Import Motors ·
                            <a href="{{ $appUrl }}" style="color:#9ca3af;text-decoration:underline;">{{ $appUrl }}</a>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>