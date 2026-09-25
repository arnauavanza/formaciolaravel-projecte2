<?php

namespace App\Services;

use App\Enums\TicketStatus;
use App\Exceptions\DomainRuleException;
use App\Models\Ticket;
use App\Repositories\Contracts\TicketRepositoryInterface;

class UpdateTicketService
{
    public function __construct(
        private TicketRepositoryInterface $tickets
    ) {}

    public function execute(Ticket $ticket, array $attributes): Ticket
    {
        if (array_key_exists('status', $attributes)) {
            $nextStatus = $attributes['status'] instanceof TicketStatus
                ? $attributes['status']
                : TicketStatus::from($attributes['status']);

            if (! $ticket->status->canTransitionTo($nextStatus)) {
                throw new DomainRuleException(
                    'Invalid ticket status transition.'
                );
            }

            $attributes['status'] = $nextStatus;

            if (
                $nextStatus === TicketStatus::Resolved
                && $ticket->resolved_at === null
            ) {
                $attributes['resolved_at'] = now();
            }
        }

        $attributes['last_activity_at'] = now();

        return $this->tickets->update($ticket, $attributes);
    }
}
