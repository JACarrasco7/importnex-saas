<?php

namespace App\Support;

use App\Mail\ErrorAlertMail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Avisa por email al responsable de la plataforma cuando hay un error 500 en
 * producción. Sin servicios de pago: usa el correo de la propia aplicación.
 * El mismo error solo avisa una vez cada 15 minutos para no inundar el buzón.
 */
final class ErrorAlert
{
    public const THROTTLE_SECONDS = 900;

    public static function recipient(): ?string
    {
        $email = trim((string) config('logging.error_alert_email'));

        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    }

    public static function notify(Throwable $e): void
    {
        $to = self::recipient();
        if ($to === null) {
            return;
        }

        $fingerprint = sha1($e::class.'|'.$e->getFile().'|'.$e->getLine());
        if (! Cache::add('error-alert:'.$fingerprint, true, self::THROTTLE_SECONDS)) {
            return;
        }

        try {
            $request = request();
            $body = implode("\n", [
                $e::class.': '.$e->getMessage(),
                $e->getFile().':'.$e->getLine(),
                '',
                'URL: '.($request->method() ?? '-').' '.$request->fullUrl(),
                'Empresa: '.($request->user()?->organization_id ?? '-').' · Usuario: '.($request->user()?->id ?? '-'),
                'Entorno: '.config('app.env'),
                '',
                mb_substr($e->getTraceAsString(), 0, 3000),
            ]);

            Mail::to($to)->send(new ErrorAlertMail(
                '['.config('app.name').'] Error: '.mb_substr(class_basename($e).' - '.$e->getMessage(), 0, 120),
                $body,
            ));
        } catch (Throwable $mailFailure) {
            // Avisar nunca debe provocar otro error: queda en el log.
            Log::warning('error_alert.failed', ['message' => $mailFailure->getMessage()]);
        }
    }
}
