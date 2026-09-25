<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\User;
use App\Repositories\Contracts\TicketRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListTicketsService
{
    public function __construct(
        private readonly TicketRepositoryInterface $tickets,
    ) {}

    public function execute(User $user): LengthAwarePaginator
    {
        return $this->tickets->paginateVisibleTo(
            $user,
            $user->can('viewAll', Ticket::class),
        );
    }
}
