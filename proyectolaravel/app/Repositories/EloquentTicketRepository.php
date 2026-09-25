<?php

namespace App\Repositories;

use App\Models\Ticket;
use App\Models\User;
use App\Repositories\Contracts\TicketRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EloquentTicketRepository implements TicketRepositoryInterface
{
    public function paginateVisibleTo(
        User $user,
        bool $viewAll,
        int $perPage = 15
    ): LengthAwarePaginator {
        $query = Ticket::query()
            ->latest();

        if (! $viewAll) {
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

        return $ticket->fresh();
    }

    public function delete(Ticket $ticket): void
    {
        $ticket->delete();
    }
}
