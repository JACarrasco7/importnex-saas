<?php

namespace App\Mail;

use App\Models\Car;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TrackingSharedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Car $car, public string $trackingUrl) {}

    public function envelope(): Envelope
    {
        $brand = $this->car->brand ?? '';
        $model = $this->car->model ?? '';

        return new Envelope(
            subject: __('tracking.shared.mail_subject', [
                'brand' => $brand,
                'model' => $model,
            ]),
            replyTo: [new Address('jjimportmotors@gmail.com', 'JJ Import Motors')],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.tracking-shared',
            text: 'emails.tracking-shared-text',
        );
    }
}
