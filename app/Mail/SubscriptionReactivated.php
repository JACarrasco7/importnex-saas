<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SubscriptionReactivated extends Mailable
{
    use SerializesModels;

    public function __construct(
        public string $userName,
        public string $orgName,
        public string $planName,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Tu suscripción ha sido reactivada',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.subscription-reactivated',
            with: [
                'userName' => $this->userName,
                'orgName' => $this->orgName,
                'planName' => $this->planName,
                'dashboardUrl' => route('dashboard'),
            ],
        );
    }
}
