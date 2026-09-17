<?php

namespace Database\Seeders;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call(RolePermissionSeeder::class);

        $testUser = User::query()->updateOrCreate([
            'email' => 'test@example.com',
        ], [
            'name' => 'Test User',
            'password' => 'password',
        ]);
        $testUser->syncRoles('admin');

        $this->call(LibrarySeeder::class);

        $customer = User::query()->updateOrCreate([
            'email' => 'customer@example.com',
        ], [
            'name' => 'Ticket Customer',
            'password' => 'password',
        ]);
        $customer->syncRoles('customer');

        $agent = User::query()->updateOrCreate([
            'email' => 'agent@example.com',
        ], [
            'name' => 'Ticket Agent',
            'password' => 'password',
        ]);
        $agent->syncRoles('agent');

        $tickets = [
            [
                'title' => 'Seed ticket 1',
                'description' => 'Example ticket assigned to the support agent.',
                'agent_id' => $agent->id,
            ],
            [
                'title' => 'Seed ticket 2',
                'description' => 'Second example ticket assigned to the support agent.',
                'agent_id' => $agent->id,
            ],
            [
                'title' => 'Seed ticket 3',
                'description' => 'Third example ticket assigned to the support agent.',
                'agent_id' => $agent->id,
            ],
            [
                'title' => 'Seed ticket 4',
                'description' => 'Example ticket waiting for an assignment.',
                'agent_id' => null,
            ],
        ];

        foreach ($tickets as $ticket) {
            Ticket::query()->updateOrCreate(
                [
                    'title' => $ticket['title'],
                    'customer_id' => $customer->id,
                ],
                [
                    'description' => $ticket['description'],
                    'status' => TicketStatus::Open,
                    'agent_id' => $ticket['agent_id'],
                    'last_activity_at' => now(),
                    'resolved_at' => null,
                    'closed_at' => null,
                ],
            );
        }
    }
}
