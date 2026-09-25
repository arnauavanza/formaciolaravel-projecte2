<?php

namespace Database\Seeders;

use App\Models\Author;
use App\Models\Book;
use App\Models\Genre;
use App\Models\Loan;
use App\Models\Member;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

class LibrarySeeder extends Seeder
{
    public function run(): void
    {
        $genres = collect([
            'Novela',
            'Poesía',
            'Teatro',
            'Ensayo',
            'Ciencia ficción',
            'Historia',
            'Infantil',
            'Misterio',
        ])->map(fn (string $name) => Genre::query()->firstOrCreate(['name' => $name]));

        $genreIds = $genres->pluck('id')->values();

        $authors = collect(range(1, 20))->mapWithKeys(
            fn (int $index): array => [
                $index => Author::query()->firstOrCreate([
                    'name' => "Seed Author {$index}",
                ]),
            ],
        );

        $books = collect(range(1, 100))->mapWithKeys(function (int $index) use ($authors, $genreIds): array {
            $author = $authors->get((($index - 1) % $authors->count()) + 1);

            $book = Book::query()->updateOrCreate(
                ['isbn' => sprintf('SEED-%04d', $index)],
                [
                    'author_id' => $author->id,
                    'title' => "Seed Book {$index}",
                    'published_year' => 2000 + ($index % 25),
                ],
            );

            $genreCount = ($index % 3) + 1;
            $bookGenreIds = collect(range(0, $genreCount - 1))
                ->map(fn (int $offset): int => $genreIds->get(($index - 1 + $offset) % $genreIds->count()))
                ->all();

            $book->genres()->syncWithoutDetaching($bookGenreIds);

            return [$index => $book];
        });

        $members = collect(range(1, 30))->mapWithKeys(
            fn (int $index): array => [
                $index => Member::query()->firstOrCreate(
                    ['name' => "Seed Member {$index}"],
                    ['phone' => sprintf('600%06d', $index)],
                ),
            ],
        );

        foreach ($books->take(25)->values() as $index => $book) {
            $member = $members->get(($index % $members->count()) + 1);
            $borrowedAt = CarbonImmutable::create(2026, 1, 1)->addDays($index);

            Loan::query()->updateOrCreate(
                [
                    'member_id' => $member->id,
                    'book_id' => $book->id,
                    'borrowed_at' => $borrowedAt,
                ],
                [
                    'due_at' => $borrowedAt->addDays(14),
                    'returned_at' => null,
                ],
            );
        }

        foreach ($books->slice(25, 40)->values() as $index => $book) {
            $member = $members->get((($index + 25) % $members->count()) + 1);
            $borrowedAt = CarbonImmutable::create(2025, 1, 1)->addDays($index);

            Loan::query()->updateOrCreate(
                [
                    'member_id' => $member->id,
                    'book_id' => $book->id,
                    'borrowed_at' => $borrowedAt,
                ],
                [
                    'due_at' => $borrowedAt->addDays(14),
                    'returned_at' => $borrowedAt->addDays(7),
                ],
            );
        }
    }
}
