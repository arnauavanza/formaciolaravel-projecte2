<?php

namespace App\Jobs;

use App\Enums\TicketStatus;
use App\Mail\TicketResolvedMail;
use App\Models\Ticket;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendTicketResolvedMail implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 60;

    public int $uniqueFor = 3600;

    public function __construct(
        public int $ticketId,
        public string $pdfPath
    ) {}

    public function handle(): void
    {
        $ticket = Ticket::query()
            ->with('customer')
            ->find($this->ticketId);

        if (
            $ticket === null
            || $ticket->status !== TicketStatus::Closed
            || $ticket->customer === null
        ) {
            return;
        }

        Mail::to($ticket->customer->email)
            ->send(new TicketResolvedMail(
                $ticket,
                $this->pdfPath,
            ));
    }

    public function uniqueId(): string
    {
        return "ticket-resolved:{$this->ticketId}";
    }
}
