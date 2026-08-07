<!DOCTYPE html>
<html lang="{{ $locale }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Nueva Alerta') }}</title>
</head>
<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif; background-color: #f5f5f5;">
    <div style="max-width: 600px; margin: 40px auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
        <!-- Header -->
        <div style="background: linear-gradient(135deg, #1e3a5f 0%, #0f172a 100%); padding: 30px; text-align: center;">
            <h1 style="margin: 0; color: #ffffff; font-size: 24px; font-weight: 600;">
                {{ __('Nueva Alerta') }}
            </h1>
        </div>

        <!-- Content -->
        <div style="padding: 30px;">
            <!-- Alert Type Badge -->
            <div style="display: inline-block; padding: 8px 16px; background: #e5e7eb; border-radius: 20px; margin-bottom: 20px;">
                <span style="font-size: 14px; font-weight: 600; color: #374151; text-transform: capitalize;">
                    {{ str_replace('_', ' ', $alert->alert_type) }}
                </span>
            </div>

            <!-- Message -->
            <p style="margin: 0 0 20px; color: #374151; font-size: 16px; line-height: 1.6;">
                {{ $alert->message }}
            </p>

            <!-- Reference Info -->
            @if ($alert->reference_id)
                <div style="background: #f9fafb; border-left: 4px solid #1e3a5f; padding: 15px; margin: 20px 0; border-radius: 4px;">
                    <p style="margin: 0; color: #6b7280; font-size: 14px;">
                        <strong>Referencia:</strong> {{ $alert->reference_id }}
                    </p>
                </div>
            @endif

            <!-- CTA Button -->
            @if ($alertUrl)
                <div style="text-align: center; margin: 30px 0;">
                    <a href="{{ $alertUrl }}" style="display: inline-block; padding: 12px 30px; background: #1e3a5f; color: #ffffff; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 15px;">
                        {{ __('Ver detalles') }}
                    </a>
                </div>
            @endif

            <!-- Timestamp -->
            <p style="margin: 20px 0 0; color: #9ca3af; font-size: 13px;">
                {{ $alert->created_at ? $alert->created_at->translatedFormat('j M Y, H:i') : '' }}
            </p>
        </div>

        <!-- Footer -->
        <div style="background: #f9fafb; padding: 20px 30px; text-align: center; border-top: 1px solid #e5e7eb;">
            <p style="margin: 0; color: #6b7280; font-size: 13px;">
                {{ $organization->name }} — JJ Import Motors
            </p>
        </div>
    </div>
</body>
</html>
