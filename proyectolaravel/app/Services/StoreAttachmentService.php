<?php

namespace App\Services;

use App\Models\Attachment;
use App\Models\Comment;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class StoreAttachmentService
{
    public function execute(
        Comment $comment,
        UploadedFile $file
    ): Attachment {
        $disk = 'local';
        $path = $file->store(
            "comments/{$comment->id}",
            $disk
        );

        try {
            return DB::transaction(function () use (
                $comment,
                $file,
                $disk,
                $path
            ) {
                $attachment = $comment->attachments()->create([
                    'disk' => $disk,
                    'path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                    'size' => $file->getSize(),
                ]);

                $comment->ticket()->update([
                    'last_activity_at' => now(),
                ]);

                return $attachment;
            });
        } catch (Throwable $exception) {
            Storage::disk($disk)->delete($path);

            throw $exception;
        }
    }
}
