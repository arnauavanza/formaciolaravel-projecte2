<?php

namespace Tests\Feature;

use App\Enums\TicketStatus;
use App\Exceptions\DomainRuleException;
use App\Models\Ticket;
use App\Repositories\Contracts\TicketRepositoryInterface;
use App\Services\UpdateTicketService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Fakes\FakeTicketRepository;
use Tests\TestCase;

class TicketUpdateServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_updates_ticket_data(): void
    {
        $ticket = Ticket::factory()->create();

        $updatedTicket = app(UpdateTicketService::class)
            ->execute($ticket, [
                'title' => 'Updated title',
                'description' => 'Updated description',
            ]);

        $this->assertSame(
            'Updated title',
            $updatedTicket->title
        );

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'title' => 'Updated title',
        ]);
    }

    public function test_it_moves_a_ticket_to_in_progress(): void
    {
        $ticket = Ticket::factory()->create([
            'status' => TicketStatus::Open,
        ]);

        $updatedTicket = app(UpdateTicketService::class)
            ->execute($ticket, [
                'status' => TicketStatus::InProgress->value,
            ]);

        $this->assertSame(
            TicketStatus::InProgress,
            $updatedTicket->status
        );
    }

    public function test_it_sets_resolved_at_when_ticket_is_resolved(): void
    {
        $ticket = Ticket::factory()->inProgress()->create();

        $updatedTicket = app(UpdateTicketService::class)
            ->execute($ticket, [
                'status' => TicketStatus::Resolved->value,
            ]);

        $this->assertSame(
            TicketStatus::Resolved,
            $updatedTicket->status
        );

        $this->assertNotNull($updatedTicket->resolved_at);
    }

    public function test_update_service_can_use_fake_repository(): void
    {
        $fakeRepository = new FakeTicketRepository;

        $this->app->instance(
            TicketRepositoryInterface::class,
            $fakeRepository
        );

        $ticket = $fakeRepository->create([
            'title' => 'Original title',
            'description' => 'Original description',
            'status' => TicketStatus::Open,
            'customer_id' => 1,
            'last_activity_at' => now(),
        ]);

        $updatedTicket = app(UpdateTicketService::class)
            ->execute($ticket, [
                'title' => 'Updated with fake',
            ]);

        $this->assertSame(
            'Updated with fake',
            $updatedTicket->title
        );
    }

    public function test_it_rejects_invalid_status_transitions(): void
    {
        $ticket = Ticket::factory()
            ->resolved()
            ->create();

        $this->expectException(DomainRuleException::class);

        app(UpdateTicketService::class)
            ->execute($ticket, [
                'status' => TicketStatus::Open->value,
            ]);
    }
}
