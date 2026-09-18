<?php

namespace Tests\Feature;

use App\Enums\TicketStatus;
use App\Models\User;
use App\Repositories\Contracts\TicketRepositoryInterface;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Fakes\FakeTicketRepository;
use Tests\TestCase;

class TicketControllerRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_index_can_use_the_fake_repository(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $fakeRepository = new FakeTicketRepository;

        $fakeRepository->create([
            'title' => 'Fake repository ticket',
            'description' => 'Ticket created in memory.',
            'status' => TicketStatus::Open,
            'customer_id' => $admin->id,
            'agent_id' => null,
            'last_activity_at' => now(),
        ]);

        $this->app->instance(
            TicketRepositoryInterface::class,
            $fakeRepository
        );

        Sanctum::actingAs($admin, [
            'tickets.read',
        ]);

        $this->getJson('/api/tickets')
            ->assertOk()
            ->assertJsonPath(
                'data.0.title',
                'Fake repository ticket'
            );
    }

    public function test_store_can_use_the_fake_repository(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $fakeRepository = new FakeTicketRepository;

        $this->app->instance(
            TicketRepositoryInterface::class,
            $fakeRepository
        );

        Sanctum::actingAs($admin, [
            'tickets.create',
        ]);

        $this->postJson('/api/tickets', [
            'title' => 'Created with fake repository',
            'description' => 'This ticket is stored in memory.',
            'customer_id' => $admin->id,
        ])
            ->assertCreated()
            ->assertJsonPath(
                'data.title',
                'Created with fake repository'
            )
            ->assertJsonPath('data.status', 'open');
    }
}
