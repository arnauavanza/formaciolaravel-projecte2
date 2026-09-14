<?php

namespace App\Http\Controllers;

use App\Http\Requests\AuthorIndexRequest;
use App\Http\Requests\StoreAuthorRequest;
use App\Http\Requests\UpdateAuthorRequest;
use App\Http\Resources\AuthorResource;
use App\Models\Author;

class AuthorController extends Controller
{
    public function index(AuthorIndexRequest $request)
    {
        $perPage = (int) ($request->validated()['per_page'] ?? 15);

        $authors = Author::query()
            ->orderBy('name')
            ->paginate($perPage);

        return AuthorResource::collection($authors);
    }

    public function store(StoreAuthorRequest $request)
    {
        $author = Author::create($request->validated());

        return (new AuthorResource($author))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Author $author)
    {
        return new AuthorResource($author);
    }

    public function update(
        UpdateAuthorRequest $request,
        Author $author
    ) {
        $author->update($request->validated());

        return new AuthorResource($author->fresh());
    }

    public function destroy(Author $author)
    {
        if ($author->books()->whereHas('loans')->exists()) {
            return response()->json([
                'message' => 'Cannot delete an author with loan history.',
            ], 409);
        }

        $author->delete();

        return response()->noContent();
    }
}
