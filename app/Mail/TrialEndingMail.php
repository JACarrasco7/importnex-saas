<?php

namespace App\Mail;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TrialEndingMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $userName,
        public string $organizationName,
        public string $trialEndsAt,
        public string $appUrl,
        public string $locale = 'es',
    ) {}

    public function envelope(): Envelope
    {
        $isEs = $this->locale === 'es';

        return new Envelope(
            subject: $isEs
                ? 'Tu prueba gratuita termina pronto'
                : 'Your free trial is ending soon',
        );
    }

    public function content(): Content
    {
        $isEs = $this->locale === 'es';
        $trialEndsDate = Carbon::parse($this->trialEndsAt)->translatedFormat('j M Y');

        return new Content(
            view: 'emails.trial-ending',
            with: [
                'userName' => $this->userName,
                'organizationName' => $this->organizationName,
                'trialEndsAt' => $trialEndsDate,
                'appUrl' => $this->appUrl,
                'locale' => $this->locale,
                'isEs' => $isEs,
            ],
        );
    }
}
