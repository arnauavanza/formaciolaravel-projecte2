<?php

namespace Tests\Fakes;

use App\Models\Ticket;
use App\Models\User;
use App\Repositories\Contracts\TicketRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Facades\Gate;

class FakeTicketRepository implements TicketRepositoryInterface
{
    /**
     * @var array<int, Ticket>
     */
    private array $tickets = [];

    private int $nextId = 1;

    public function paginateVisibleTo(
        User $user,
        int $perPage = 15
    ): LengthAwarePaginator {
        $tickets = collect($this->tickets);

        if (! Gate::forUser($user)->allows('viewAll', Ticket::class)) {
            $tickets = $tickets->filter(
                fn (Ticket $ticket): bool => $ticket->customer_id === $user->id
                    || $ticket->agent_id === $user->id
            );
        }

        return new Paginator(
            $tickets->values(),
            $tickets->count(),
            $perPage,
            1,
            ['path' => '/api/tickets'],
        );
    }

    public function findOrFail(int|string $id): Ticket
    {
        $id = (int) $id;

        if (! isset($this->tickets[$id])) {
            throw (new ModelNotFoundException)
                ->setModel(Ticket::class, [$id]);
        }

        return $this->tickets[$id];
    }

    public function create(array $attributes): Ticket
    {
        $ticket = new Ticket;
        $ticket->fill($attributes);
        $ticket->id = $this->nextId++;

        $this->tickets[$ticket->id] = $ticket;

        return $ticket;
    }

    public function update(
        Ticket $ticket,
        array $attributes
    ): Ticket {
        $ticket->fill($attributes);
        $this->tickets[$ticket->id] = $ticket;

        return $ticket;
    }

    public function delete(Ticket $ticket): void
    {
        unset($this->tickets[$ticket->id]);
    }
}
