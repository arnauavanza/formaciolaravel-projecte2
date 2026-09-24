<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAttachmentRequest;
use App\Http\Resources\AttachmentResource;
use App\Models\Attachment;
use App\Models\Comment;
use App\Services\DownloadAttachmentService;
use App\Services\StoreAttachmentService;

class AttachmentController extends Controller
{
    public function store(
        StoreAttachmentRequest $request,
        Comment $comment,
        StoreAttachmentService $service
    ) {
        $this->authorize('comment', $comment->ticket);

        return (new AttachmentResource(
            $service->execute($comment, $request->file('file'))
        ))
            ->response()
            ->setStatusCode(201);
    }

    public function download(
        Comment $comment,
        Attachment $attachment,
        DownloadAttachmentService $service
    ) {
        $this->authorize('view', $comment->ticket);

        return $service->execute($comment, $attachment);
    }
}
