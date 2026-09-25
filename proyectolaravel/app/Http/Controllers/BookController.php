<?php

namespace App\Http\Controllers;

use App\Http\Requests\BookIndexRequest;
use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookRequest;
use App\Http\Resources\BookResource;
use App\Models\Book;
use App\Services\CreateBookService;
use App\Services\DeleteBookService;
use App\Services\ListBooksService;
use App\Services\UpdateBookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class BookController extends Controller
{
    public function index(
        BookIndexRequest $request,
        ListBooksService $service,
    ): AnonymousResourceCollection {
        return BookResource::collection(
            $service->execute($request->validated()),
        );
    }

    public function store(
        StoreBookRequest $request,
        CreateBookService $service,
    ): JsonResponse {
        return (new BookResource(
            $service->execute($request->validated()),
        ))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Book $book): BookResource
    {
        $book->load(['author', 'genres']);

        return new BookResource($book);
    }

    public function update(
        UpdateBookRequest $request,
        Book $book,
        UpdateBookService $service,
    ): BookResource {
        return new BookResource(
            $service->execute($book, $request->validated()),
        );
    }

    public function destroy(
        Book $book,
        DeleteBookService $service,
    ): Response {
        $service->execute($book);

        return response()->noContent();
    }
}
