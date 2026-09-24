<?php

namespace App\Services;

use App\Models\Book;

class UpdateBookService
{
    public function execute(Book $book, array $attributes): Book
    {
        $hasGenres = array_key_exists('genre_ids', $attributes);
        $genreIds = $attributes['genre_ids'] ?? [];

        unset($attributes['genre_ids']);

        $book->update($attributes);

        if ($hasGenres) {
            $book->genres()->sync($genreIds);
        }

        return $book->fresh(['author', 'genres']);
    }
}
