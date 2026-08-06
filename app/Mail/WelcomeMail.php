<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $userName,
        public string $organizationName,
        public string $appUrl,
        public string $locale = 'es',
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->locale === 'es'
                ? 'Bienvenido a JJ Import Motors'
                : 'Welcome to JJ Import Motors',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.welcome',
            with: [
                'userName' => $this->userName,
                'organizationName' => $this->organizationName,
                'appUrl' => $this->appUrl,
                'locale' => $this->locale,
            ],
        );
    }
}
