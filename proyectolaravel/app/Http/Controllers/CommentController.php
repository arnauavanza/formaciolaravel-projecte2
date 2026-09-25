<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCommentRequest;
use App\Http\Resources\CommentResource;
use App\Models\Comment;
use App\Models\Ticket;
use App\Services\StoreCommentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CommentController extends Controller
{
    public function index(Ticket $ticket): AnonymousResourceCollection
    {
        $this->authorize('view', $ticket);

        $comments = $ticket->comments()
            ->with(['user', 'attachments'])
            ->latest()
            ->paginate(20);

        return CommentResource::collection($comments);
    }

    public function store(
        StoreCommentRequest $request,
        Ticket $ticket,
        StoreCommentService $service,
    ): JsonResponse {
        return $this->createdCommentResponse(
            $service->execute(
                $ticket,
                $request->user(),
                $request->validated('body'),
            ),
        );
    }

    private function createdCommentResponse(Comment $comment): JsonResponse
    {
        return (new CommentResource($comment))
            ->response()
            ->setStatusCode(201);
    }
}
