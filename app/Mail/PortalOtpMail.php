<?php

namespace App\Mail;

use App\Models\ImportOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Código de verificación de 6 dígitos para firmar o aprobar en el portal. */
class PortalOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public ImportOrder $order, public string $code, public string $purpose) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('orders.mail.otp_subject', ['code' => $this->code]));
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.portal-otp',
            text: 'emails.portal-otp-text',
            with: [
                'purposeText' => __('orders.mail.otp_purpose_'.$this->purpose),
                'minutes' => (int) config('import_orders.otp_ttl_minutes', 10),
            ],
        );
    }
}
