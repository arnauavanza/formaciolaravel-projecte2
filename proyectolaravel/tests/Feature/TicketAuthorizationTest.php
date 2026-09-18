<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TicketAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_customer_can_only_view_their_own_tickets(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $ownTicket = Ticket::factory()
            ->for($customer, 'customer')
            ->create();
        $otherTicket = Ticket::factory()->create();

        $this->authenticate($customer);

        $this->getJson('/api/tickets')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $ownTicket->id);

        $this->getJson("/api/tickets/{$ownTicket->id}")
            ->assertOk();

        $this->getJson("/api/tickets/{$otherTicket->id}")
            ->assertForbidden();
    }

    public function test_agent_can_update_assigned_tickets_only(): void
    {
        $agent = User::factory()->create();
        $agent->assignRole('agent');

        $assignedTicket = Ticket::factory()
            ->for($agent, 'agent')
            ->create();
        $unassignedTicket = Ticket::factory()->create([
            'agent_id' => null,
        ]);

        $this->authenticate($agent);

        $this->patchJson("/api/tickets/{$assignedTicket->id}", [
            'title' => 'Updated by agent',
        ])
            ->assertOk()
            ->assertJsonPath('data.title', 'Updated by agent');

        $this->patchJson("/api/tickets/{$unassignedTicket->id}", [
            'title' => 'Should be rejected',
        ])
            ->assertForbidden();
    }

    public function test_customer_cannot_delete_a_ticket(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');
        $ticket = Ticket::factory()
            ->for($customer, 'customer')
            ->create();

        $this->authenticate($customer);

        $this->deleteJson("/api/tickets/{$ticket->id}")
            ->assertForbidden();
    }

    public function test_admin_can_delete_an_open_ticket(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $ticket = Ticket::factory()->create();

        $this->authenticate($admin);

        $this->deleteJson("/api/tickets/{$ticket->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('tickets', [
            'id' => $ticket->id,
        ]);
    }

    public function test_closed_ticket_cannot_be_updated_or_deleted(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $ticket = Ticket::factory()->closed()->create();

        $this->authenticate($admin);

        $this->patchJson("/api/tickets/{$ticket->id}", [
            'title' => 'Should be rejected',
        ])
            ->assertForbidden();

        $this->deleteJson("/api/tickets/{$ticket->id}")
            ->assertForbidden();
    }

    public function test_customer_ticket_is_owned_by_authenticated_user(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');
        $otherUser = User::factory()->create();

        $this->authenticate($customer);

        $this->postJson('/api/tickets', [
            'title' => 'Private ticket',
            'description' => 'The authenticated customer owns this ticket.',
            'customer_id' => $otherUser->id,
        ])
            ->assertCreated();

        $this->assertDatabaseHas('tickets', [
            'title' => 'Private ticket',
            'customer_id' => $customer->id,
        ]);
    }

    public function test_supervisor_can_view_all_tickets_but_cannot_modify_them(): void
    {
        $supervisor = User::factory()->create();
        $supervisor->assignRole('supervisor');

        $firstTicket = Ticket::factory()->create();
        $secondTicket = Ticket::factory()->create();

        $this->authenticate($supervisor);

        $this->getJson('/api/tickets')
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $this->getJson("/api/tickets/{$firstTicket->id}")
            ->assertOk();

        $this->patchJson("/api/tickets/{$firstTicket->id}", [
            'title' => 'Should be rejected',
        ])
            ->assertForbidden();

        $this->deleteJson("/api/tickets/{$secondTicket->id}")
            ->assertForbidden();
    }

    public function test_supervisor_can_assign_an_active_agent(): void
    {
        $supervisor = User::factory()->create();
        $supervisor->assignRole('supervisor');

        $agent = User::factory()->create();
        $ticket = Ticket::factory()->create();

        $this->authenticate($supervisor);

        $this->postJson("/api/tickets/{$ticket->id}/assign", [
            'agent_id' => $agent->id,
        ])
            ->assertOk()
            ->assertJsonPath('data.agent_id', $agent->id);
    }

    public function test_only_resolved_tickets_can_be_closed(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $openTicket = Ticket::factory()->create();
        $resolvedTicket = Ticket::factory()
            ->resolved()
            ->create();

        $this->authenticate($admin);

        $this->postJson("/api/tickets/{$openTicket->id}/close")
            ->assertForbidden();

        $this->postJson("/api/tickets/{$resolvedTicket->id}/close")
            ->assertOk()
            ->assertJsonPath('data.status', 'closed');
    }

    private function authenticate(User $user): void
    {
        Sanctum::actingAs($user, [
            'tickets.read',
            'tickets.create',
            'tickets.update',
            'tickets.delete',
            'tickets.assign',
            'tickets.close',
        ]);
    }
}
