<?php

namespace App\Services;

use App\Models\Book;

class CreateBookService
{
    public function execute(array $attributes): Book
    {
        $genreIds = $attributes['genre_ids'] ?? [];

        unset($attributes['genre_ids']);

        $book = Book::create($attributes);
        $book->genres()->sync($genreIds);

        return $book->load(['author', 'genres']);
    }
}
