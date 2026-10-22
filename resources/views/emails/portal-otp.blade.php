<!doctype html>
<html lang="{{ app()->getLocale() === 'en' ? 'en' : 'es' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('orders.mail.otp_heading') }}</title>
</head>
<body style="margin:0;padding:0;background-color:#f3f4f7;font-family:'Segoe UI',Arial,Helvetica,sans-serif;color:#1c2030;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f3f4f7;padding:24px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background-color:#ffffff;border-radius:12px;overflow:hidden;">
                    <tr>
                        <td style="background:#1A306D;padding:24px 32px;">
                            <h1 style="margin:0;color:#ffffff;font-size:20px;font-weight:700;">{{ __('orders.mail.otp_heading') }}</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:28px 32px;font-size:15px;line-height:1.6;text-align:center;">
                            <p style="margin:0 0 16px;">{{ __('orders.mail.otp_line1', ['purpose' => $purposeText, 'reference' => $order->reference]) }}</p>
                            <p style="margin:0 0 20px;font-size:34px;font-weight:700;letter-spacing:10px;color:#1A306D;font-family:Consolas,Menlo,monospace;">{{ $code }}</p>
                            <p style="margin:0;font-size:13px;color:#4a5266;">{{ __('orders.mail.otp_line2', ['minutes' => $minutes]) }}</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:16px 32px;background:#f3f4f7;font-size:12px;color:#7a8398;">{{ $order->organization->brand()->footerLine() }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
