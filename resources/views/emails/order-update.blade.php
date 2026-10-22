<!doctype html>
<html lang="{{ app()->getLocale() === 'en' ? 'en' : 'es' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $subjectLine }}</title>
</head>
<body style="margin:0;padding:0;background-color:#f3f4f7;font-family:'Segoe UI',Arial,Helvetica,sans-serif;color:#1c2030;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f3f4f7;padding:24px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background-color:#ffffff;border-radius:12px;overflow:hidden;">
                    <tr>
                        <td style="background:#1A306D;padding:24px 32px;">
                            <p style="margin:0 0 4px;color:#BEC0C3;font-size:12px;letter-spacing:0.08em;text-transform:uppercase;">JJ Import Motors</p>
                            <h1 style="margin:0;color:#ffffff;font-size:20px;font-weight:700;">{{ $heading }}</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:28px 32px;font-size:15px;line-height:1.6;">
                            @if ($greeting)
                                <p style="margin:0 0 16px;">{{ $greeting }}</p>
                            @endif
                            @foreach ($lines as $line)
                                <p style="margin:0 0 14px;">{!! nl2br(e($line)) !!}</p>
                            @endforeach
                            @if ($actionUrl && $actionLabel)
                                <p style="margin:24px 0 28px;text-align:center;">
                                    <a href="{{ $actionUrl }}" style="display:inline-block;background:#1A306D;color:#ffffff;text-decoration:none;font-weight:700;padding:14px 28px;border-radius:8px;">{{ $actionLabel }}</a>
                                </p>
                            @endif
                            @if ($forClient)
                                <p style="margin:0;white-space:pre-line;">{{ __('orders.mail.signature') }}</p>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:16px 32px;background:#f3f4f7;font-size:12px;color:#7a8398;">
                            @if ($forClient)
                                {{ __('orders.mail.footer') }}<br>
                            @endif
                            JJ Import Motors · Huelva, España
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
