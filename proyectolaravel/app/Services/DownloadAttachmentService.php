<?php

namespace App\Services;

use App\Models\Attachment;
use Illuminate\Support\Facades\Storage;

class DownloadAttachmentService
{
    public function resolve(Attachment $attachment): ?Attachment
    {
        $disk = Storage::disk($attachment->disk);

        if (! $disk->exists($attachment->path)) {
            return null;
        }

        return $attachment;
    }
}
