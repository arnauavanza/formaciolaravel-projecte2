<?php

namespace Tests\Feature;

use App\Models\Author;
use App\Models\Book;
use App\Models\Genre;
use App\Models\Loan;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BookApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_books_can_be_listed_with_relations(): void
    {
        $book = Book::factory()->create();
        $genre = Genre::factory()->create();

        $book->genres()->attach($genre);

        $this->getJson('/api/books')
            ->assertOk()
            ->assertJsonPath('data.0.id', $book->id)
            ->assertJsonPath('data.0.author.id', $book->author_id)
            ->assertJsonPath('data.0.genres.0.id', $genre->id);
    }

    public function test_book_index_avoids_n_plus_one_queries(): void
    {
        Book::factory()->count(5)->create();

        $queryCount = 0;

        DB::listen(function (QueryExecuted $query) use (&$queryCount): void {
            $queryCount++;
        });

        $this->getJson('/api/books')
            ->assertOk();

        $this->assertLessThanOrEqual(6, $queryCount);
    }

    public function test_books_can_be_sorted_and_paginated(): void
    {
        Book::factory()->create(['title' => 'Zeta']);
        Book::factory()->create(['title' => 'Alpha']);

        $this->getJson('/api/books?sort=title&direction=asc&per_page=1')
            ->assertOk()
            ->assertJsonPath('data.0.title', 'Alpha')
            ->assertJsonPath('meta.per_page', 1);
    }

    public function test_book_can_be_created_with_genres(): void
    {
        $author = Author::factory()->create();
        $genre = Genre::factory()->create();

        $response = $this->postJson('/api/books', [
            'author_id' => $author->id,
            'title' => 'Nuevo libro',
            'isbn' => '9781234567890',
            'published_year' => 2024,
            'genre_ids' => [$genre->id],
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.title', 'Nuevo libro');

        $bookId = $response->json('data.id');

        $this->assertDatabaseHas('books', [
            'id' => $bookId,
            'author_id' => $author->id,
            'title' => 'Nuevo libro',
        ]);

        $this->assertDatabaseHas('book_genre', [
            'book_id' => $bookId,
            'genre_id' => $genre->id,
        ]);
    }

    public function test_book_validation_works(): void
    {
        $this->postJson('/api/books', [
            'author_id' => 999999,
            'title' => '',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'author_id',
                'title',
            ]);
    }

    public function test_book_can_be_shown(): void
    {
        $book = Book::factory()->create();

        $this->getJson("/api/books/{$book->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $book->id);
    }

    public function test_book_can_be_updated(): void
    {
        $book = Book::factory()->create([
            'isbn' => '9781234567890',
        ]);

        $author = Author::factory()->create();
        $genre = Genre::factory()->create();

        $this->patchJson("/api/books/{$book->id}", [
            'author_id' => $author->id,
            'title' => 'Libro actualizado',
            'isbn' => '9781234567890',
            'published_year' => 2025,
            'genre_ids' => [$genre->id],
        ])
            ->assertOk()
            ->assertJsonPath('data.title', 'Libro actualizado');

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => 'Libro actualizado',
            'author_id' => $author->id,
        ]);

        $this->assertDatabaseHas('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $genre->id,
        ]);
    }

    public function test_book_without_loans_can_be_deleted(): void
    {
        $book = Book::factory()->create();

        $this->deleteJson("/api/books/{$book->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('books', [
            'id' => $book->id,
        ]);
    }

    public function test_book_with_loans_cannot_be_deleted(): void
    {
        $book = Book::factory()->create();

        Loan::factory()->create([
            'book_id' => $book->id,
        ]);

        $this->deleteJson("/api/books/{$book->id}")
            ->assertStatus(409);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
        ]);
    }
}
