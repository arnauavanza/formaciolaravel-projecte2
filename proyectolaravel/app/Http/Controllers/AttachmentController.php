<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAttachmentRequest;
use App\Http\Resources\AttachmentResource;
use App\Models\Attachment;
use App\Models\Comment;
use Illuminate\Support\Facades\Storage;

class AttachmentController extends Controller
{
    public function store(
        StoreAttachmentRequest $request,
        Comment $comment
    ) {
        $this->authorize('comment', $comment->ticket);

        $file = $request->file('file');
        $disk = 'local';
        $path = $file->store(
            "comments/{$comment->id}",
            $disk
        );

        $attachment = $comment->attachments()->create([
            'disk' => $disk,
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize(),
        ]);

        $comment->ticket()->update([
            'last_activity_at' => now(),
        ]);

        return (new AttachmentResource($attachment))
            ->response()
            ->setStatusCode(201);
    }

    public function download(
        Comment $comment,
        Attachment $attachment
    ) {
        $this->authorize('view', $comment->ticket);

        abort_unless(
            $attachment->comment_id === $comment->id,
            404
        );

        $disk = Storage::disk($attachment->disk);

        abort_unless(
            $disk->exists($attachment->path),
            404
        );

        return $disk->download(
            $attachment->path,
            $attachment->original_name
        );
    }
}
