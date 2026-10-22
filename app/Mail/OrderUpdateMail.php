<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Email genérico del flujo de encargos (cliente y equipo): encabezado,
 * párrafos, un botón y adjuntos opcionales. Así todos los avisos comparten
 * diseño y se redactan en OrderNotifier.
 */
class OrderUpdateMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  list<string>  $lines
     * @param  list<array{path: string, name: string, mime: string}>  $files
     */
    public function __construct(
        public string $subjectLine,
        public string $heading,
        public array $lines,
        public ?string $actionLabel = null,
        public ?string $actionUrl = null,
        public ?string $greeting = null,
        public bool $forClient = true,
        public array $files = [],
        public array $brand = [],
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subjectLine,
            replyTo: $this->replyToAddresses(),
        );
    }

    /** @return list<Address> */
    private function replyToAddresses(): array
    {
        $email = config('import_orders.contact_email') ?: ($this->brand['email'] ?? null);

        return $email ? [new Address((string) $email, (string) ($this->brand['name'] ?? config('app.name')))] : [];
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.order-update',
            text: 'emails.order-update-text',
        );
    }

    /** @return list<Attachment> */
    public function attachments(): array
    {
        return array_map(
            fn (array $file) => Attachment::fromStorageDisk('local', $file['path'])->as($file['name'])->withMime($file['mime']),
            $this->files,
        );
    }
}
