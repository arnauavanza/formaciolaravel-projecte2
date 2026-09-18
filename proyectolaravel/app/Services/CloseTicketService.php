<?php

namespace App\Services;

use App\Enums\TicketStatus;
use App\Jobs\GenerateTicketHistoryPdf;
use App\Models\Ticket;
use App\Repositories\Contracts\TicketRepositoryInterface;
use DomainException;

class CloseTicketService
{
    public function __construct(
        private TicketRepositoryInterface $tickets
    ) {}

    public function execute(Ticket $ticket): Ticket
    {
        if ($ticket->status !== TicketStatus::Resolved) {
            throw new DomainException(
                'Only resolved tickets can be closed.'
            );
        }

        $now = now();

        $updatedTicket = $this->tickets->update($ticket, [
            'status' => TicketStatus::Closed,
            'resolved_at' => $ticket->resolved_at ?? $now,
            'closed_at' => $now,
            'last_activity_at' => $now,
        ]);

        GenerateTicketHistoryPdf::dispatch($updatedTicket->id);

        return $updatedTicket;
    }
}
