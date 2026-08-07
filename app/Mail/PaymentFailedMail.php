<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentFailedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $userName,
        public string $organizationName,
        public string $graceDays,
        public string $appUrl,
        public string $locale = 'es',
    ) {}

    public function envelope(): Envelope
    {
        $isEs = $this->locale === 'es';

        return new Envelope(
            subject: $isEs
                ? 'Pago fallado - Tu suscripción necesita atención'
                : 'Payment failed - Your subscription needs attention',
        );
    }

    public function content(): Content
    {
        $isEs = $this->locale === 'es';

        return new Content(
            view: 'emails.payment-failed',
            with: [
                'userName' => $this->userName,
                'organizationName' => $this->organizationName,
                'graceDays' => $this->graceDays,
                'appUrl' => $this->appUrl,
                'locale' => $this->locale,
                'isEs' => $isEs,
            ],
        );
    }
}
