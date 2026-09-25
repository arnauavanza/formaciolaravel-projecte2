<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CommentApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_customer_can_comment_on_their_ticket(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $ticket = Ticket::factory()
            ->for($customer, 'customer')
            ->create();

        $this->authenticate($customer);

        $this->postJson("/api/tickets/{$ticket->id}/comments", [
            'body' => 'I still need help.',
        ])
            ->assertCreated()
            ->assertJsonPath('data.body', 'I still need help.');

        $this->assertDatabaseHas('comments', [
            'ticket_id' => $ticket->id,
            'user_id' => $customer->id,
            'body' => 'I still need help.',
        ]);
    }

    public function test_customer_cannot_comment_on_another_users_ticket(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $ticket = Ticket::factory()->create();

        $this->authenticate($customer);

        $this->postJson("/api/tickets/{$ticket->id}/comments", [
            'body' => 'This should be rejected.',
        ])
            ->assertForbidden();
    }

    public function test_comments_can_be_listed(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $ticket = Ticket::factory()
            ->for($customer, 'customer')
            ->create();

        $ticket->comments()->create([
            'user_id' => $customer->id,
            'body' => 'Existing comment.',
        ]);

        $this->authenticate($customer);

        $this->getJson("/api/tickets/{$ticket->id}/comments")
            ->assertOk()
            ->assertJsonPath('data.0.body', 'Existing comment.');
    }

    private function authenticate(User $user): void
    {
        Sanctum::actingAs($user, [
            'tickets.read',
            'comments.create',
        ]);
    }
}
