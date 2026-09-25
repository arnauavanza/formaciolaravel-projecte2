<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
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
        Storage::fake('local');

        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $ticket = Ticket::factory()
            ->closed()
            ->for($customer, 'customer')
            ->create();

        $ticket->comments()->create([
            'user_id' => $customer->id,
            'body' => 'Comment included in the PDF.',
        ]);

        Storage::disk('local')->put(
            "tickets/{$ticket->id}/history.pdf",
            'fake-pdf'
        );

        Sanctum::actingAs($customer, [
            'tickets.read',
        ]);

        $response = $this->get("/api/tickets/{$ticket->id}/pdf");

        $response
            ->assertRedirect()
            ->assertRedirectContains(
                "/tickets/{$ticket->id}/history.pdf"
            );

        $this->assertStringContainsString(
            'expiration=',
            $response->headers->get('Location')
        );
    }

    public function test_pdf_is_not_available_before_ticket_is_closed(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $ticket = Ticket::factory()
            ->inProgress()
            ->for($customer, 'customer')
            ->create();

        Sanctum::actingAs($customer, [
            'tickets.read',
        ]);

        $this->getJson("/api/tickets/{$ticket->id}/pdf")
            ->assertStatus(409)
            ->assertJsonPath(
                'message',
                'The PDF is available after the ticket is closed.'
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
