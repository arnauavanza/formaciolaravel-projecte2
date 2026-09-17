<?php

namespace App\Policies;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;

class TicketPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('tickets.read');
    }

    public function view(User $user, Ticket $ticket): bool
    {
        return $user->hasRole('admin')
            || (
                $user->can('tickets.read')
                && in_array($user->id, [
                    $ticket->customer_id,
                    $ticket->agent_id,
                ], true)
            );
    }

    public function create(User $user): bool
    {
        return $user->can('tickets.create');
    }

    public function update(User $user, Ticket $ticket): bool
    {
        if ($ticket->status === TicketStatus::Closed) {
            return false;
        }

        return $user->hasRole('admin')
            || (
                $user->can('tickets.update')
                && $ticket->agent_id === $user->id
            );
    }

    public function delete(User $user, Ticket $ticket): bool
    {
        return $ticket->status !== TicketStatus::Closed
            && $user->can('tickets.delete');
    }
}
