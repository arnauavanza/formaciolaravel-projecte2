<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAttachmentRequest;
use App\Http\Resources\AttachmentResource;
use App\Models\Attachment;
use App\Models\Comment;
use App\Services\DownloadAttachmentService;
use App\Services\StoreAttachmentService;
use Illuminate\Support\Facades\Storage;

class AttachmentController extends Controller
{
    public function store(
        StoreAttachmentRequest $request,
        Comment $comment,
        StoreAttachmentService $service
    ) {
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

        $download = $service->resolve($comment, $attachment);

        abort_unless($download !== null, 404);

        return Storage::disk($download['disk'])->download(
            $download['path'],
            $download['name'],
        );
    }
}
