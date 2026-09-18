<?php

namespace App\Mail;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TicketResolvedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Ticket $ticket,
        public string $pdfPath
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Ticket resolved: {$this->ticket->title}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.tickets.resolved',
            with: [
                'ticket' => $this->ticket,
            ],
        );
    }

    public function attachments(): array
    {
        return [
            Attachment::fromStorageDisk(
                'local',
                $this->pdfPath
            )
                ->as("ticket-{$this->ticket->id}-history.pdf")
                ->withMime('application/pdf'),
        ];
    }
}
