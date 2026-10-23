<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Aviso de error 500 para el responsable de la plataforma (texto plano). */
class ErrorAlertMail extends Mailable
{
    public function __construct(public string $subjectLine, public string $report) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->subjectLine);
    }

    public function content(): Content
    {
        return new Content(text: 'emails.error-alert');
    }
}
