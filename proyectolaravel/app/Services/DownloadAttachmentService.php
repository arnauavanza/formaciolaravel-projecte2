<?php

namespace App\Services;

use App\Models\Attachment;
use App\Models\Comment;
use Illuminate\Support\Facades\Storage;

class DownloadAttachmentService
{
    public function resolve(
        Comment $comment,
        Attachment $attachment
    ): ?array {
        if ($attachment->comment_id !== $comment->id) {
            return null;
        }

        $disk = Storage::disk($attachment->disk);

        if (! $disk->exists($attachment->path)) {
            return null;
        }

        return [
            'disk' => $attachment->disk,
            'path' => $attachment->path,
            'name' => $attachment->original_name,
        ];
    }
}
