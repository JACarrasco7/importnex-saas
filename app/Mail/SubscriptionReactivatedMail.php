<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SubscriptionReactivatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $userName,
        public string $organizationName,
        public string $planName,
        public string $appUrl,
        public string $locale = 'es',
    ) {}

    public function envelope(): Envelope
    {
        $isEs = $this->locale === 'es';

        return new Envelope(
            subject: $isEs
                ? '¡Suscripción reactivada!'
                : 'Subscription reactivated!',
        );
    }

    public function content(): Content
    {
        $isEs = $this->locale === 'es';

        return new Content(
            view: 'emails.subscription-reactivated',
            with: [
                'userName' => $this->userName,
                'organizationName' => $this->organizationName,
                'planName' => $this->planName,
                'appUrl' => $this->appUrl,
                'locale' => $this->locale,
                'isEs' => $isEs,
            ],
        );
    }
}
