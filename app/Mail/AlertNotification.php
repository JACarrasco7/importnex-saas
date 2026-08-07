<?php

namespace App\Mail;

use App\Models\Alert;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AlertNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Alert $alert,
        public Organization $organization,
        public User $user,
        $locale = 'es',
    ) {
        $this->locale = $locale;
    }

    public function envelope(): Envelope
    {
        $emoji = match ($this->alert->alert_type) {
            'verification_failed' => '⚠️',
            'verification_completed' => '✅',
            'car_request' => '📩',
            'car_stale' => '🕒',
            'client_no_contact' => '👤',
            default => '🔔',
        };

        $alertType = str_replace('_', ' ', $this->alert->alert_type);

        $subject = $this->locale === 'es'
            ? "{$emoji} Nueva alerta: {$alertType}"
            : "{$emoji} New alert: {$alertType}";

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.alert',
            with: [
                'alert' => $this->alert,
                'organization' => $this->organization,
                'user' => $this->user,
                'locale' => $this->locale,
                'appUrl' => rtrim(config('app.url'), '/'),
                'alertUrl' => $this->alert->target_url,
            ],
        );
    }
}
