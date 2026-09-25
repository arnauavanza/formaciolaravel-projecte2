<?php

namespace Tests\Feature;

use App\Repositories\Contracts\TicketRepositoryInterface;
use App\Repositories\EloquentTicketRepository;
use Tests\TestCase;

class RepositoryBindingTest extends TestCase
{
    public function test_ticket_repository_resolves_to_eloquent_implementation(): void
    {
        $repository = $this->app->make(
            TicketRepositoryInterface::class
        );

        $this->assertInstanceOf(
            EloquentTicketRepository::class,
            $repository
        );
    }
}
