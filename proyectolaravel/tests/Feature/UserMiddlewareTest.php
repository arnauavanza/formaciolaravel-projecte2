<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\Sanctum;
use Mockery;
use Tests\TestCase;

class UserMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_active_user_can_access_tickets_and_request_is_logged(): void
    {
        Log::spy();

        $user = User::factory()->create();
        $user->assignRole('customer');

        Sanctum::actingAs($user, [
            'tickets.read',
        ]);

        $this->getJson('/api/tickets')
            ->assertOk();

        Log::shouldHaveReceived('info')
            ->once()
            ->with(
                'Authenticated request',
                Mockery::on(fn (array $context): bool => $context['user_id'] === $user->id
                    && $context['method'] === 'GET'
                    && $context['path'] === 'api/tickets'),
            );
    }

    public function test_inactive_user_cannot_access_tickets(): void
    {
        $user = User::factory()
            ->inactive()
            ->create();

        $user->assignRole('customer');

        Sanctum::actingAs($user, [
            'tickets.read',
        ]);

        $this->getJson('/api/tickets')
            ->assertForbidden()
            ->assertJson([
                'message' => 'User account is inactive.',
            ]);
    }

    public function test_unauthenticated_user_cannot_access_tickets(): void
    {
        $this->getJson('/api/tickets')
            ->assertUnauthorized();
    }
}
