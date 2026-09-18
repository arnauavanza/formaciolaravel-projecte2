<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TicketPdfTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_customer_can_download_their_ticket_history(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $ticket = Ticket::factory()
            ->for($customer, 'customer')
            ->create();

        $ticket->comments()->create([
            'user_id' => $customer->id,
            'body' => 'Comment included in the PDF.',
        ]);

        Sanctum::actingAs($customer, [
            'tickets.read',
        ]);

        $this->get("/api/tickets/{$ticket->id}/pdf")
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertHeader(
                'content-disposition',
                "attachment; filename=\"ticket-{$ticket->id}-history.pdf\""
            );
    }

    public function test_customer_cannot_download_another_users_ticket_history(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $ticket = Ticket::factory()->create();

        Sanctum::actingAs($customer, [
            'tickets.read',
        ]);

        $this->get("/api/tickets/{$ticket->id}/pdf")
            ->assertForbidden();
    }
}
