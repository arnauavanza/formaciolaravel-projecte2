<?php

namespace App\Services;

use App\Models\User;
use App\Policies\TicketPolicy;
use App\Repositories\Contracts\TicketRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListTicketsService
{
    public function __construct(
        private TicketRepositoryInterface $tickets,
        private TicketPolicy $policy
    ) {}

    public function execute(User $user): LengthAwarePaginator
    {
        return $this->tickets->paginateVisibleTo(
            $user,
            $this->policy->viewAll($user),
        );
    }
}
