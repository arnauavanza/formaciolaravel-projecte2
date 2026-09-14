<?php

namespace Database\Seeders;

use App\Models\Author;
use App\Models\Book;
use App\Models\Genre;
use App\Models\Loan;
use App\Models\Member;
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

        $authors = Author::factory(20)->create();

        $books = Book::factory(100)
            ->recycle($authors)
            ->create()
            ->each(function (Book $book) use ($genres) {
                $book->genres()->attach(
                    $genres->random(rand(1, 3))->pluck('id')->all()
                );
            });

        $members = Member::factory(30)->create();

        $books->shuffle()->take(25)->each(function (Book $book) use ($members): void {
            Loan::factory()
                ->active()
                ->recycle($members)
                ->for($book)
                ->create();
        });

        Loan::factory(40)
            ->returned()
            ->recycle($members)
            ->recycle($books)
            ->create();
    }
}
