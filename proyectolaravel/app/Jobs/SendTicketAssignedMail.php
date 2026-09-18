<?php

namespace App\Jobs;

use App\Mail\TicketAssignedMail;
use App\Models\Ticket;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendTicketAssignedMail implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 60;

    public int $uniqueFor = 3600;

    public function __construct(
        public int $ticketId,
        public int $agentId
    ) {}

    public function handle(): void
    {
        $ticket = Ticket::query()
            ->with('agent')
            ->find($this->ticketId);

        if (
            $ticket === null
            || $ticket->agent_id !== $this->agentId
            || $ticket->agent === null
        ) {
            return;
        }

        Mail::to($ticket->agent->email)
            ->send(new TicketAssignedMail($ticket));
    }

    public function uniqueId(): string
    {
        return "ticket-assigned:{$this->ticketId}:{$this->agentId}";
    }
}
