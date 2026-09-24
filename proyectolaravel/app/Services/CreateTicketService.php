<?php

namespace App\Services;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Repositories\Contracts\TicketRepositoryInterface;

class CreateTicketService
{
    public function __construct(
        private TicketRepositoryInterface $tickets
    ) {}

    public function execute(array $attributes): Ticket
    {
        return $this->tickets->create([
            ...$attributes,
            'status' => TicketStatus::Open,
            'last_activity_at' => now(),
        ]);
    }
}
