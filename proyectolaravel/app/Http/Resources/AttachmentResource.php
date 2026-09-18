<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttachmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'comment_id' => $this->comment_id,
            'original_name' => $this->original_name,
            'mime_type' => $this->mime_type,
            'size' => $this->size,
            'download_url' => route(
                'comments.attachments.download',
                [
                    'comment' => $this->comment_id,
                    'attachment' => $this->id,
                ],
            ),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
