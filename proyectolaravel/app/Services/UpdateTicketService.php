<?php

namespace App\Services;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Repositories\Contracts\TicketRepositoryInterface;
use DomainException;

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

            if (! $this->canTransition($ticket->status, $nextStatus)) {
                throw new DomainException(
                    'Invalid ticket status transition.'
                );
            }

            $attributes['status'] = $nextStatus;

            if ($nextStatus === TicketStatus::Resolved) {
                $attributes['resolved_at'] = now();
            }
        }

        $attributes['last_activity_at'] = now();

        return $this->tickets->update($ticket, $attributes);
    }

    private function canTransition(
        TicketStatus $currentStatus,
        TicketStatus $nextStatus
    ): bool {
        if ($currentStatus === $nextStatus) {
            return true;
        }

        return match ($currentStatus) {
            TicketStatus::Open => $nextStatus === TicketStatus::InProgress,
            TicketStatus::InProgress => $nextStatus === TicketStatus::Resolved,
            TicketStatus::Resolved => false,
            TicketStatus::Closed => false,
        };
    }
}
