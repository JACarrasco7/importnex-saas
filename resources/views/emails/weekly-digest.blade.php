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
                    <tr>
                        <td style="background:linear-gradient(135deg,#1A306D,#0f1d44);padding:32px 24px;text-align:center;">
                            <h1 style="margin:0;color:#ffffff;font-size:22px;font-weight:700;letter-spacing:-0.5px;">
                                {{ $isEs ? 'Resumen semanal' : 'Weekly digest' }}
                            </h1>
                            <p style="margin:8px 0 0;color:#cbd5e1;font-size:14px;">
                                {{ $organization->name }} · {{ now()->subWeek()->format('d M') }} – {{ now()->format('d M Y') }}
                            </p>
                        </td>
                    </tr>

                    @php
                        $new = $stats['new_week'] ?? 0;
                        $resolved = $stats['resolved_week'] ?? 0;
                        $pending = $stats['pending'] ?? 0;
                        $snoozed = $stats['snoozed'] ?? 0;
                    @endphp

                    <tr>
                        <td style="padding:32px 32px 16px;color:#1f2937;font-size:15px;line-height:1.6;">
                            <p style="margin:0 0 16px;">
                                {{ $isEs
                                    ? 'Aquí tienes lo que ha pasado en tu flota la última semana.'
                                    : 'Here is what happened in your fleet over the last week.' }}
                            </p>

                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin:16px 0;">
                                <tr>
                                    <td align="center" style="padding:8px;">
                                        <div style="background-color:#eef2ff;border-radius:8px;padding:16px;">
                                            <p style="margin:0;font-size:24px;font-weight:700;color:#1A306D;">{{ $new }}</p>
                                            <p style="margin:4px 0 0;font-size:12px;color:#4b5563;">{{ $isEs ? 'nuevas' : 'new' }}</p>
                                        </div>
                                    </td>
                                    <td align="center" style="padding:8px;">
                                        <div style="background-color:#d1fae5;border-radius:8px;padding:16px;">
                                            <p style="margin:0;font-size:24px;font-weight:700;color:#065f46;">{{ $resolved }}</p>
                                            <p style="margin:4px 0 0;font-size:12px;color:#065f46;">{{ $isEs ? 'resueltas' : 'resolved' }}</p>
                                        </div>
                                    </td>
                                    <td align="center" style="padding:8px;">
                                        <div style="background-color:#fef3c7;border-radius:8px;padding:16px;">
                                            <p style="margin:0;font-size:24px;font-weight:700;color:#92400e;">{{ $pending }}</p>
                                            <p style="margin:4px 0 0;font-size:12px;color:#92400e;">{{ $isEs ? 'pendientes' : 'pending' }}</p>
                                        </div>
                                    </td>
                                </tr>
                            </table>

                            @if($snoozed > 0)
                                <p style="margin:0 0 16px;font-size:13px;color:#6b7280;">
                                    {{ $isEs
                                        ? "Tienes {$snoozed} alertas pospuestas que se reactivarán solas."
                                        : "You have {$snoozed} snoozed alerts that will reactivate automatically." }}
                                </p>
                            @endif
                        </td>
                    </tr>

                    @if(count($recentAlerts) > 0)
                        <tr>
                            <td style="padding:16px 32px;color:#1f2937;font-size:14px;line-height:1.6;">
                                <h2 style="margin:0 0 12px;font-size:16px;color:#1f2937;">
                                    {{ $isEs ? 'Últimas alertas' : 'Recent alerts' }}
                                </h2>
                                @foreach($recentAlerts as $alert)
                                    @php
                                        // Soporte tanto para Eloquent models como para arrays
                                        $type = is_array($alert) ? ($alert['alert_type'] ?? null) : $alert->alert_type;
                                        $message = is_array($alert) ? ($alert['message'] ?? '') : $alert->message;
                                        $createdAt = is_array($alert) ? (isset($alert['created_at']) ? \Illuminate\Support\Carbon::parse($alert['created_at']) : null) : $alert->created_at;
                                        $targetUrl = is_array($alert) ? ($alert['target_url'] ?? null) : $alert->target_url;
                                        $typeLabel = match($type) {
                                            'car_request' => $isEs ? 'Solicitud de vehículo' : 'Car request',
                                            'car_stale' => $isEs ? 'Vehículo sin actividad' : 'Stale car',
                                            'client_no_contact' => $isEs ? 'Cliente sin contacto' : 'Client no contact',
                                            'verification_failed' => $isEs ? 'Verificación fallida' : 'Verification failed',
                                            'verification_completed' => $isEs ? 'Verificación completada' : 'Verification completed',
                                            default => (string) $type,
                                        };
                                    @endphp
                                    <div style="border-left:3px solid #1A306D;background-color:#f9fafb;padding:12px 16px;margin-bottom:8px;border-radius:0 6px 6px 0;">
                                        <p style="margin:0 0 4px;font-size:13px;font-weight:600;color:#1A306D;">{{ $typeLabel }}</p>
                                        <p style="margin:0;font-size:13px;color:#374151;">{{ $message }}</p>
                                        <p style="margin:4px 0 0;font-size:11px;color:#9ca3af;">
                                            {{ $createdAt ? $createdAt->diffForHumans() : '' }}
                                            @if($targetUrl)
                                                · <a href="{{ $targetUrl }}" style="color:#1A306D;">{{ $isEs ? 'Ver' : 'View' }}</a>
                                            @endif
                                        </p>
                                    </div>
                                @endforeach
                            </td>
                        </tr>
                    @endif

                    <tr>
                        <td style="padding:24px 32px;text-align:center;">
                            <a href="{{ $appUrl }}/alerts" style="display:inline-block;background-color:#1A306D;color:#ffffff;padding:12px 24px;border-radius:8px;text-decoration:none;font-size:14px;font-weight:600;">
                                {{ $isEs ? 'Abrir panel de alertas' : 'Open alerts panel' }}
                            </a>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:24px 32px;background-color:#f9fafb;font-size:12px;color:#9ca3af;text-align:center;border-top:1px solid #e5e7eb;">
                            <p style="margin:0;">
                                {{ $isEs ? 'Recibes este email porque tienes alertas activas en JJ Import Motors.' : 'You receive this email because you have active alerts on JJ Import Motors.' }}
                            </p>
                            <p style="margin:8px 0 0;">
                                <a href="{{ $appUrl }}/organization/{{ $organization->id }}/edit" style="color:#6b7280;text-decoration:underline;">
                                    {{ $isEs ? 'Cambiar preferencias' : 'Change preferences' }}
                                </a>
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
