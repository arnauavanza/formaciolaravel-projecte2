<?php

namespace App\Services;

use App\Models\Attachment;
use App\Models\Comment;
use Illuminate\Support\Facades\Storage;

class DownloadAttachmentService
{
    public function execute(Comment $comment, Attachment $attachment)
    {
        abort_unless($attachment->comment_id === $comment->id, 404);

        $disk = Storage::disk($attachment->disk);

        abort_unless($disk->exists($attachment->path), 404);

        return $disk->download(
            $attachment->path,
            $attachment->original_name
        );
    }
}
