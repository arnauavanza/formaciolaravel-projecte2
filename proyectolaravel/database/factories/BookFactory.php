<?php

namespace Database\Factories;

use App\Models\Author;
use Illuminate\Database\Eloquent\Factories\Factory;

class BookFactory extends Factory
{
    public function definition(): array
    {
        return [
            'author_id' => Author::factory(),
            'title' => fake()->sentence(3),
            'isbn' => fake()->unique()->isbn13(),
            'published_year' => fake()->numberBetween(1800, (int) date('Y')),
        ];
    }
}
