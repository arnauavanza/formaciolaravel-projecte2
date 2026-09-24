<?php

namespace App\Services;

use App\Enums\TicketStatus;
use App\Jobs\SendTicketAssignedMail;
use App\Models\Ticket;
use App\Models\User;
use App\Repositories\Contracts\TicketRepositoryInterface;
use DomainException;

class AssignTicketService
{
    public function __construct(
        private TicketRepositoryInterface $tickets
    ) {}

    public function execute(Ticket $ticket, int $agentId): Ticket
    {
        $agent = User::query()->findOrFail($agentId);

        if ($ticket->status === TicketStatus::Closed) {
            throw new DomainException(
                'Closed tickets cannot be assigned.'
            );
        }

        if (! $agent->is_active) {
            throw new DomainException(
                'Inactive users cannot be assigned as agents.'
            );
        }

        $updatedTicket = $this->tickets->update($ticket, [
            'agent_id' => $agent->id,
            'last_activity_at' => now(),
        ]);

        SendTicketAssignedMail::dispatch(
            $updatedTicket->id,
            $agent->id,
        );

        return $updatedTicket;
    }
}
