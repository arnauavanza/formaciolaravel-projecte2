<?php

namespace Tests\Feature;

use App\Enums\TicketStatus;
use App\Exceptions\DomainRuleException;
use App\Jobs\GenerateTicketHistoryPdf;
use App\Jobs\SendTicketAssignedMail;
use App\Models\Ticket;
use App\Models\User;
use App\Services\AssignTicketService;
use App\Services\CloseTicketService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class TicketWorkflowServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        Queue::fake();
    }

    public function test_assign_ticket_service_assigns_an_active_agent(): void
    {
        $ticket = Ticket::factory()->create();
        $agent = User::factory()->create();
        $agent->assignRole('agent');

        $updatedTicket = app(AssignTicketService::class)
            ->execute($ticket, $agent->id);

        $this->assertSame($agent->id, $updatedTicket->agent_id);

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'agent_id' => $agent->id,
        ]);

        Queue::assertPushed(
            SendTicketAssignedMail::class,
            fn (SendTicketAssignedMail $job): bool => $job->ticketId === $ticket->id
                && $job->agentId === $agent->id,
        );
    }

    public function test_close_ticket_service_closes_a_resolved_ticket(): void
    {
        $ticket = Ticket::factory()
            ->resolved()
            ->create();

        $closedTicket = app(CloseTicketService::class)
            ->execute($ticket);

        $this->assertSame(
            TicketStatus::Closed,
            $closedTicket->status
        );

        $this->assertNotNull($closedTicket->closed_at);

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'status' => 'closed',
        ]);

        Queue::assertPushed(
            GenerateTicketHistoryPdf::class,
            fn (GenerateTicketHistoryPdf $job): bool => $job->ticketId === $ticket->id,
        );
    }

    public function test_open_ticket_cannot_be_closed(): void
    {
        $ticket = Ticket::factory()->create();

        $this->expectException(DomainRuleException::class);

        app(CloseTicketService::class)
            ->execute($ticket);
    }

    public function test_closed_ticket_cannot_be_assigned(): void
    {
        $ticket = Ticket::factory()
            ->closed()
            ->create();

        $agent = User::factory()->create();
        $agent->assignRole('agent');

        $this->expectException(DomainRuleException::class);

        app(AssignTicketService::class)
            ->execute($ticket, $agent->id);
    }
}
