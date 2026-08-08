<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TrialEndingSoon extends Mailable
{
    use SerializesModels;

    public function __construct(
        public string $userName,
        public string $orgName,
        public int $daysRemaining,
        public string $trialEndDate,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Tu prueba gratis termina en ' . $this->daysRemaining . ' días',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.trial-ending-soon',
            with: [
                'userName' => $this->userName,
                'orgName' => $this->orgName,
                'daysRemaining' => $this->daysRemaining,
                'trialEndDate' => $this->trialEndDate,
                'pricingUrl' => route('pricing'),
            ],
        );
    }
}