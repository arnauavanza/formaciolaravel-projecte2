<?php

namespace Tests\Feature;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketDomainTest extends TestCase
{
    use RefreshDatabase;

    public function test_ticket_has_a_customer_and_an_optional_agent(): void
    {
        $customer = User::factory()->create();

        $ticket = Ticket::factory()
            ->for($customer, 'customer')
            ->create([
                'agent_id' => null,
            ]);

        $this->assertTrue($ticket->customer->is($customer));
        $this->assertNull($ticket->agent);
        $this->assertSame(TicketStatus::Open, $ticket->status);
    }

    public function test_ticket_can_be_assigned_to_an_agent(): void
    {
        $customer = User::factory()->create();
        $agent = User::factory()->create();

        $ticket = Ticket::factory()
            ->for($customer, 'customer')
            ->for($agent, 'agent')
            ->create();

        $this->assertTrue($ticket->customer->is($customer));
        $this->assertTrue($ticket->agent->is($agent));
    }
}
