<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCommentRequest;
use App\Http\Resources\CommentResource;
use App\Models\Ticket;

class CommentController extends Controller
{
    public function index(Ticket $ticket)
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
        Ticket $ticket
    ) {
        $this->authorize('comment', $ticket);

        $comment = $ticket->comments()->create([
            'user_id' => $request->user()->id,
            'body' => $request->validated('body'),
        ]);

        $ticket->update([
            'last_activity_at' => now(),
        ]);

        return (new CommentResource($comment->load(['user', 'attachments'])))
            ->response()
            ->setStatusCode(201);
    }
}
