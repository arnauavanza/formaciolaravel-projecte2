<?php

namespace Tests\Unit;

use App\Enums\TicketStatus;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Tests\Fakes\FakeTicketRepository;
use Tests\TestCase;

class FakeTicketRepositoryTest extends TestCase
{
    public function test_fake_repository_can_create_update_and_delete_tickets(): void
    {
        $repository = new FakeTicketRepository;

        $ticket = $repository->create([
            'title' => 'Test ticket',
            'description' => 'Test description',
            'status' => TicketStatus::Open,
            'customer_id' => 1,
            'last_activity_at' => now(),
        ]);

        $this->assertSame('Test ticket', $ticket->title);

        $updatedTicket = $repository->update($ticket, [
            'title' => 'Updated ticket',
        ]);

        $this->assertSame('Updated ticket', $updatedTicket->title);

        $repository->delete($ticket);

        $this->expectException(ModelNotFoundException::class);

        $repository->findOrFail($ticket->id);
    }
}
