<?php

namespace App\Http\Controllers;

use App\Http\Requests\AuthorIndexRequest;
use App\Http\Requests\StoreAuthorRequest;
use App\Http\Requests\UpdateAuthorRequest;
use App\Http\Resources\AuthorResource;
use App\Models\Author;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class AuthorController extends Controller
{
    public function index(AuthorIndexRequest $request): AnonymousResourceCollection
    {
        $perPage = (int) ($request->validated()['per_page'] ?? 15);

        $authors = Author::query()
            ->orderBy('name')
            ->paginate($perPage);

        return AuthorResource::collection($authors);
    }

    public function store(StoreAuthorRequest $request): JsonResponse
    {
        $author = Author::create($request->validated());

        return (new AuthorResource($author))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Author $author): AuthorResource
    {
        return new AuthorResource($author);
    }

    public function update(
        UpdateAuthorRequest $request,
        Author $author,
    ): AuthorResource {
        $author->update($request->validated());

        return new AuthorResource($author->fresh());
    }

    public function destroy(Author $author): JsonResponse|Response
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
