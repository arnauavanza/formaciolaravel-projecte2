<?php

namespace App\Http\Controllers;

use App\Http\Requests\BookIndexRequest;
use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookRequest;
use App\Http\Resources\BookResource;
use App\Models\Book;

class BookController extends Controller
{
    public function index(BookIndexRequest $request)
    {
        $filters = $request->validated();

        $books = Book::query()
            ->with(['author', 'genres'])
            ->orderBy(
                $filters['sort'] ?? 'title',
                $filters['direction'] ?? 'asc'
            )
            ->paginate($filters['per_page'] ?? 15);

        return BookResource::collection($books);
    }

    public function store(StoreBookRequest $request)
    {
        $validated = $request->validated();
        $genreIds = $validated['genre_ids'] ?? [];

        unset($validated['genre_ids']);

        $book = Book::create($validated);
        $book->genres()->sync($genreIds);
        $book->load(['author', 'genres']);

        return (new BookResource($book))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Book $book)
    {
        $book->load(['author', 'genres']);

        return new BookResource($book);
    }

    public function update(
        UpdateBookRequest $request,
        Book $book
    ) {
        $validated = $request->validated();
        $hasGenres = array_key_exists('genre_ids', $validated);
        $genreIds = $validated['genre_ids'] ?? [];

        unset($validated['genre_ids']);

        $book->update($validated);

        if ($hasGenres) {
            $book->genres()->sync($genreIds);
        }

        return new BookResource(
            $book->fresh(['author', 'genres'])
        );
    }

    public function destroy(Book $book)
    {
        if ($book->loans()->exists()) {
            return response()->json([
                'message' => 'Cannot delete a book with loan history.',
            ], 409);
        }

        $book->delete();

        return response()->noContent();
    }
}
