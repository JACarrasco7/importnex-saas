<?php

namespace App\Services\ImportOrders;

use App\Mail\PortalOtpMail;
use App\Models\ImportOrder;
use App\Models\PortalOtp;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Mail;

/**
 * Códigos de verificación (OTP) de 6 dígitos enviados al email del cliente
 * para los actos con valor: firmar el contrato, aprobar costes y decisiones.
 *
 * - Se guarda solo el HMAC del código (nunca el código).
 * - Caduca en N minutos, máximo N intentos, máximo N envíos por hora.
 * - Pedir un código nuevo invalida el anterior del mismo acto.
 */
class PortalOtpService
{
    /** @throws OrderFlowException */
    public function send(ImportOrder $order, string $purpose, ?Model $subject, ?string $ip): PortalOtp
    {
        $email = $order->client?->email;
        if (! $email) {
            throw new OrderFlowException('No tenemos tu email para enviarte el código. Escríbenos y lo actualizamos.');
        }

        $sentLastHour = PortalOtp::query()
            ->where('import_order_id', $order->id)
            ->where('created_at', '>=', now()->subHour())
            ->count();
        if ($sentLastHour >= (int) config('import_orders.otp_max_sends_per_hour', 5)) {
            throw new OrderFlowException('Has pedido demasiados códigos. Espera unos minutos y vuelve a intentarlo.', 'code');
        }

        $this->openQuery($order, $purpose, $subject)->update(['expires_at' => now()]);

        $code = (string) random_int(100000, 999999);

        $otp = PortalOtp::create([
            'organization_id' => $order->organization_id,
            'import_order_id' => $order->id,
            'purpose' => $purpose,
            'subject_type' => $subject ? $subject->getMorphClass() : null,
            'subject_id' => $subject?->getKey(),
            'code_hash' => $this->hash($code),
            'channel' => 'email',
            'sent_to' => $email,
            'expires_at' => now()->addMinutes((int) config('import_orders.otp_ttl_minutes', 10)),
            'ip' => $ip,
        ]);

        Mail::to($email)->send(new PortalOtpMail($order, $code, $purpose));

        return $otp;
    }

    /**
     * Valida y consume el código.
     *
     * @throws OrderFlowException
     */
    public function verify(ImportOrder $order, string $purpose, ?Model $subject, ?string $code): PortalOtp
    {
        $code = preg_replace('/\D+/', '', (string) $code);
        if (strlen((string) $code) !== 6) {
            throw new OrderFlowException('Escribe el código de 6 dígitos que te hemos enviado por email.', 'code');
        }

        /** @var PortalOtp|null $otp */
        $otp = $this->openQuery($order, $purpose, $subject)->latest('id')->first();
        if (! $otp) {
            throw new OrderFlowException('El código ha caducado. Pide uno nuevo.', 'code');
        }

        $maxAttempts = (int) config('import_orders.otp_max_attempts', 3);
        if ($otp->attempts >= $maxAttempts) {
            throw new OrderFlowException('Has superado los intentos. Pide un código nuevo.', 'code');
        }

        if (! hash_equals($otp->code_hash, $this->hash($code))) {
            $otp->increment('attempts');
            $left = $maxAttempts - $otp->attempts;

            throw new OrderFlowException(
                $left > 0 ? "Código incorrecto. Te quedan {$left} intentos." : 'Código incorrecto. Pide un código nuevo.',
                'code',
            );
        }

        $otp->forceFill(['consumed_at' => now()])->save();

        return $otp;
    }

    /** Destino enmascarado para mostrar al cliente (jo***@gmail.com). */
    public static function maskEmail(?string $email): ?string
    {
        if (! $email || ! str_contains($email, '@')) {
            return null;
        }
        [$user, $domain] = explode('@', $email, 2);

        return mb_substr($user, 0, 2).str_repeat('*', max(1, mb_strlen($user) - 2)).'@'.$domain;
    }

    /** @return \Illuminate\Database\Eloquent\Builder<PortalOtp> */
    private function openQuery(ImportOrder $order, string $purpose, ?Model $subject)
    {
        return PortalOtp::query()
            ->where('import_order_id', $order->id)
            ->where('purpose', $purpose)
            ->where('subject_type', $subject?->getMorphClass())
            ->where('subject_id', $subject?->getKey())
            ->whereNull('consumed_at')
            ->where('expires_at', '>', now());
    }

    private function hash(string $code): string
    {
        return hash_hmac('sha256', $code, (string) config('app.key'));
    }
}
