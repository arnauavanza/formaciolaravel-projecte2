<?php

namespace App\Services;

use App\Enums\TicketStatus;
use App\Exceptions\DomainRuleException;
use App\Jobs\SendTicketAssignedMail;
use App\Models\Ticket;
use App\Models\User;
use App\Repositories\Contracts\TicketRepositoryInterface;

class AssignTicketService
{
    public function __construct(
        private TicketRepositoryInterface $tickets
    ) {}

    public function execute(Ticket $ticket, int $agentId): Ticket
    {
        if ($ticket->status === TicketStatus::Closed) {
            throw new DomainRuleException(
                'Closed tickets cannot be assigned.'
            );
        }

        $agent = User::query()
            ->role('agent')
            ->where('is_active', true)
            ->find($agentId);

        if ($agent === null) {
            throw new DomainRuleException(
                'Only active agents can be assigned.'
            );
        }

        $updatedTicket = $this->tickets->update($ticket, [
            'agent_id' => $agent->id,
            'last_activity_at' => now(),
        ]);

        SendTicketAssignedMail::dispatch(
            $updatedTicket->id,
            $agent->id,
        )->afterCommit();

        return $updatedTicket;
    }
}
