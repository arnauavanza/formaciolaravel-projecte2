<?php

namespace Database\Seeders;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $this->call(LibrarySeeder::class);
        $customer = User::factory()->create([
            'name' => 'Ticket Customer',
            'email' => 'customer@example.com',
        ]);

        $agent = User::factory()->create([
            'name' => 'Ticket Agent',
            'email' => 'agent@example.com',
        ]);

        Ticket::factory(3)
            ->for($customer, 'customer')
            ->for($agent, 'agent')
            ->create();

        Ticket::factory()
            ->for($customer, 'customer')
            ->create();
    }
}
