<?php

namespace App\Http\Controllers;

use App\Http\Requests\BookIndexRequest;
use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookRequest;
use App\Http\Resources\BookResource;
use App\Models\Book;
use App\Services\CreateBookService;
use App\Services\ListBooksService;
use App\Services\UpdateBookService;

class BookController extends Controller
{
    public function index(
        BookIndexRequest $request,
        ListBooksService $service
    )
    {
        return BookResource::collection(
            $service->execute($request->validated())
        );
    }

    public function store(
        StoreBookRequest $request,
        CreateBookService $service
    )
    {
        return (new BookResource(
            $service->execute($request->validated())
        ))
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
        Book $book,
        UpdateBookService $service
    ) {
        return new BookResource(
            $service->execute($book, $request->validated())
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
