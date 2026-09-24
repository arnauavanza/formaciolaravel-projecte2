<?php

namespace App\Services;

use App\Models\Attachment;
use App\Models\Comment;
use Illuminate\Http\UploadedFile;

class StoreAttachmentService
{
    public function execute(Comment $comment, UploadedFile $file): Attachment
    {
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

        return $attachment;
    }
}
