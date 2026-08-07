<?php

namespace App\Mail;

use App\Models\Organization;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WeeklyAlertDigest extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Organization $organization,
        public array $stats,
        public array $recentAlerts,
        public string $locale = 'es',
    ) {}

    public function envelope(): Envelope
    {
        $isEs = $this->locale === 'es';
        $resolved = $this->stats['resolved_week'] ?? 0;
        $new = $this->stats['new_week'] ?? 0;
        $pending = $this->stats['pending'] ?? 0;

        $subject = $isEs
            ? sprintf('Tu resumen semanal — %d nuevas, %d resueltas, %d pendientes', $new, $resolved, $pending)
            : sprintf('Your weekly digest — %d new, %d resolved, %d pending', $new, $resolved, $pending);

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.weekly-digest',
            with: [
                'organization' => $this->organization,
                'stats' => $this->stats,
                'recentAlerts' => $this->recentAlerts,
                'locale' => $this->locale,
                'appUrl' => rtrim(config('app.url'), '/'),
            ],
        );
    }
}
