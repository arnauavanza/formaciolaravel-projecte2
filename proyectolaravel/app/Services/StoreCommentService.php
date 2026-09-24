<?php

namespace App\Services;

use App\Models\Comment;
use App\Models\Ticket;
use App\Models\User;

class StoreCommentService
{
    public function execute(
        Ticket $ticket,
        User $user,
        string $body
    ): Comment {
        $comment = $ticket->comments()->create([
            'user_id' => $user->id,
            'body' => $body,
        ]);

        $ticket->update([
            'last_activity_at' => now(),
        ]);

        return $comment->load(['user', 'attachments']);
    }
}
