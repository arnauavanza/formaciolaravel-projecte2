<?php

namespace App\Repositories;

use App\Models\Ticket;
use App\Models\User;
use App\Repositories\Contracts\TicketRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;

class EloquentTicketRepository implements TicketRepositoryInterface
{
    public function paginateVisibleTo(
        User $user,
        int $perPage = 15
    ): LengthAwarePaginator {
        $query = Ticket::query()
            ->with(['customer', 'agent'])
            ->latest();

        if (! Gate::forUser($user)->allows('viewAll', Ticket::class)) {
            $query->where(function ($query) use ($user) {
                $query
                    ->where('customer_id', $user->id)
                    ->orWhere('agent_id', $user->id);
            });
        }

        return $query->paginate($perPage);
    }

    public function findOrFail(int|string $id): Ticket
    {
        return Ticket::query()->findOrFail($id);
    }

    public function create(array $attributes): Ticket
    {
        return Ticket::query()->create($attributes);
    }

    public function update(
        Ticket $ticket,
        array $attributes
    ): Ticket {
        $ticket->update($attributes);

        return $ticket->fresh(['customer', 'agent']);
    }

    public function delete(Ticket $ticket): void
    {
        $ticket->delete();
    }
}
