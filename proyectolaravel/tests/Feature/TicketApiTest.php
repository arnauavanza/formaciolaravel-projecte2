<?php

namespace Tests\Feature;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TicketApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_tickets_can_be_listed(): void
    {
        $this->authenticate();

        Ticket::factory()->count(2)->create();

        $this->getJson('/api/tickets')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'description',
                        'status',
                        'customer_id',
                        'agent_id',
                    ],
                ],
                'links',
                'meta',
            ]);
    }

    public function test_ticket_can_be_created(): void
    {
        $customer = $this->authenticate();

        $this->postJson('/api/tickets', [
            'title' => 'No puedo iniciar sesión',
            'description' => 'La contraseña no funciona.',
            'customer_id' => $customer->id,
        ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'open');

        $this->assertDatabaseHas('tickets', [
            'title' => 'No puedo iniciar sesión',
            'status' => 'open',
            'customer_id' => $customer->id,
        ]);
    }

    public function test_customer_can_create_a_ticket_without_customer_id(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $customer = User::factory()->create();
        $customer->assignRole('customer');

        Sanctum::actingAs($customer, [
            'tickets.read',
            'tickets.create',
        ]);

        $this->postJson('/api/tickets', [
            'title' => 'No puedo acceder a mi cuenta',
            'description' => 'El acceso falla.',
        ])
            ->assertCreated()
            ->assertJsonPath('data.customer_id', $customer->id);
    }

    public function test_ticket_creation_is_validated(): void
    {
        $this->authenticate();

        $this->postJson('/api/tickets', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'title',
                'description',
                'customer_id',
            ]);
    }

    public function test_ticket_can_move_to_in_progress(): void
    {
        $this->authenticate();

        $ticket = Ticket::factory()->create([
            'status' => TicketStatus::Open,
        ]);

        $this->patchJson("/api/tickets/{$ticket->id}", [
            'status' => 'in_progress',
        ])
            ->assertOk()
            ->assertJsonPath('data.status', 'in_progress');
    }

    public function test_invalid_ticket_transition_is_rejected(): void
    {
        $this->authenticate();

        $ticket = Ticket::factory()
            ->resolved()
            ->create();

        $this->patchJson("/api/tickets/{$ticket->id}", [
            'status' => 'open',
        ])
            ->assertUnprocessable()
            ->assertJsonPath(
                'message',
                'Invalid ticket status transition.'
            );
    }

    public function test_ticket_can_be_deleted(): void
    {
        $this->authenticate();

        $ticket = Ticket::factory()->create();

        $this->deleteJson("/api/tickets/{$ticket->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('tickets', [
            'id' => $ticket->id,
        ]);
    }

    private function authenticate(): User
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('admin');

        Sanctum::actingAs($user, [
            'tickets.read',
            'tickets.create',
            'tickets.update',
            'tickets.delete',
        ]);

        return $user;
    }
}
