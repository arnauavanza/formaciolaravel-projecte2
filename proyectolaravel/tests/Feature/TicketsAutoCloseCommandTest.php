<?php

namespace Tests\Feature;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class TicketsAutoCloseCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_closes_old_resolved_tickets(): void
    {
        Queue::fake();

        $oldTicket = Ticket::factory()
            ->resolved()
            ->create([
                'last_activity_at' => now()->subDays(8),
            ]);

        $recentTicket = Ticket::factory()
            ->resolved()
            ->create([
                'last_activity_at' => now()->subDays(2),
            ]);

        $openTicket = Ticket::factory()->create([
            'last_activity_at' => now()->subDays(10),
        ]);

        $this->artisan('tickets:auto-close')
            ->assertExitCode(0);

        $this->assertDatabaseHas('tickets', [
            'id' => $oldTicket->id,
            'status' => TicketStatus::Closed->value,
        ]);

        $this->assertDatabaseHas('tickets', [
            'id' => $recentTicket->id,
            'status' => TicketStatus::Resolved->value,
        ]);

        $this->assertDatabaseHas('tickets', [
            'id' => $openTicket->id,
            'status' => TicketStatus::Open->value,
        ]);
    }

    public function test_dry_run_does_not_close_tickets(): void
    {
        $ticket = Ticket::factory()
            ->resolved()
            ->create([
                'last_activity_at' => now()->subDays(8),
            ]);

        $this->artisan('tickets:auto-close', [
            '--dry-run' => true,
        ])
            ->assertExitCode(0)
            ->expectsOutput("Would close ticket #{$ticket->id}.");

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'status' => TicketStatus::Resolved->value,
        ]);
    }
}
