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

    public function viewAll(User $user): bool
    {
        return $user->can('tickets.view_all');
    }

    public function view(User $user, Ticket $ticket): bool
    {
        return $user->can('tickets.view_all')
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

    public function createForAnotherUser(User $user): bool
    {
        return $user->can('tickets.create_for_others');
    }

    public function assign(User $user, Ticket $ticket): bool
    {
        return $ticket->status !== TicketStatus::Closed
            && $user->can('tickets.assign');
    }

    public function close(User $user, Ticket $ticket): bool
    {
        return $ticket->status === TicketStatus::Resolved
            && $user->can('tickets.close');
    }

    public function update(User $user, Ticket $ticket): bool
    {
        if ($ticket->status === TicketStatus::Closed) {
            return false;
        }

        return $user->can('tickets.update_all')
            || (
                $user->can('tickets.update')
                && $ticket->agent_id === $user->id
            );
    }

    public function delete(User $user, Ticket $ticket): bool
    {
        if ($ticket->status === TicketStatus::Closed) {
            return false;
        }

        if ($user->can('tickets.delete_all')) {
            return true;
        }

        return $user->can('tickets.delete')
            && in_array($user->id, [
                $ticket->customer_id,
                $ticket->agent_id,
            ], true);
    }

    public function comment(User $user, Ticket $ticket): bool
    {
        return $user->can('comments.create')
            && (
                $user->can('tickets.view_all')
                || in_array($user->id, [
                    $ticket->customer_id,
                    $ticket->agent_id,
                ], true)
            );
    }
}
