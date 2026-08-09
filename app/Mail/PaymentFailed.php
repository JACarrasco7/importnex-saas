<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentFailed extends Mailable
{
    use SerializesModels;

    public function __construct(
        public string $userName,
        public string $orgName,
        public int $graceDays,
        public string $nextPaymentDate,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Error en el pago de tu suscripción',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.payment-failed',
            with: [
                'userName' => $this->userName,
                'orgName' => $this->orgName,
                'graceDays' => $this->graceDays,
                'nextPaymentDate' => $this->nextPaymentDate,
                'billingUrl' => route('billing.index'),
            ],
        );
    }
}
