<?php

namespace Database\Factories;

use App\Models\Book;
use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;

class LoanFactory extends Factory
{
    public function definition(): array
    {
        $borrowedAt = fake()->dateTimeBetween('-1 year', 'now');

        return [
            'member_id' => Member::factory(),
            'book_id' => Book::factory(),
            'borrowed_at' => $borrowedAt,
            'due_at' => (clone $borrowedAt)->modify('+14 days'),
            'returned_at' => null,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'returned_at' => null,
        ]);
    }

    public function returned(): static
    {
        return $this->state(function (array $attributes) {
            $borrowedAt = $attributes['borrowed_at'] ?? now()->subDays(20);

            return [
                'returned_at' => fake()->dateTimeBetween($borrowedAt, 'now'),
            ];
        });
    }
}
