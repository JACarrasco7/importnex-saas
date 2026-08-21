<!doctype html>
<html lang="{{ app()->getLocale() === 'en' ? 'en' : 'es' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('tracking.shared.mail_subject', ['brand' => $car->brand, 'model' => $car->model]) }}</title>
</head>
<body style="margin:0;padding:0;background-color:#f3f4f7;font-family:'Segoe UI',Arial,Helvetica,sans-serif;color:#1c2030;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f3f4f7;padding:24px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="background-color:#ffffff;border-radius:12px;overflow:hidden;">
                    <!-- Header -->
                    <tr>
                        <td style="background:linear-gradient(135deg,#1f4e79 0%,#0e2c46 100%);padding:24px 32px;">
                            <h1 style="margin:0;color:#ffffff;font-size:20px;font-weight:700;">
                                {{ __('tracking.shared.mail_subject', ['brand' => $car->brand, 'model' => $car->model]) }}
                            </h1>
                        </td>
                    </tr>
                    <!-- Body -->
                    <tr>
                        <td style="padding:28px 32px;font-size:15px;line-height:1.6;">
                            <p style="margin:0 0 16px;">
                                {{ __('tracking.shared.mail_intro', ['brand' => $car->brand, 'model' => $car->model, 'year' => $car->year]) }}
                            </p>
                            <p style="margin:0 0 24px;">
                                {{ __('tracking.shared.mail_body') }}
                            </p>
                            <p style="margin:0 0 28px;text-align:center;">
                                <a href="{{ $trackingUrl }}" style="display:inline-block;background:#1f4e79;color:#ffffff;text-decoration:none;font-weight:700;padding:14px 28px;border-radius:8px;">
                                    {{ __('tracking.shared.mail_cta') }}
                                </a>
                            </p>
                            <p style="margin:0;font-size:13px;color:#4a5266;">
                                {{ __('tracking.shared.mail_footer') }}
                            </p>
                        </td>
                    </tr>
                    <!-- Footer -->
                    <tr>
                        <td style="padding:16px 32px;background:#f3f4f7;font-size:12px;color:#7a8398;">
                            JJ Import Motors · Huelva, España · {{ config('app.name') }}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
