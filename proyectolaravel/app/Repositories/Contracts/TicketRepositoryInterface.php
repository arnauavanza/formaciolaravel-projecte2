<?php

namespace App\Repositories\Contracts;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface TicketRepositoryInterface
{
    public function paginateVisibleTo(
        User $user,
        int $perPage = 15
    ): LengthAwarePaginator;

    public function findOrFail(int|string $id): Ticket;

    public function create(array $attributes): Ticket;

    public function update(
        Ticket $ticket,
        array $attributes
    ): Ticket;

    public function delete(Ticket $ticket): void;
}
