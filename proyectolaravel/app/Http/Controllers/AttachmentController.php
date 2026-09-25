<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAttachmentRequest;
use App\Http\Resources\AttachmentResource;
use App\Models\Attachment;
use App\Models\Comment;
use App\Services\DownloadAttachmentService;
use App\Services\StoreAttachmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttachmentController extends Controller
{
    public function store(
        StoreAttachmentRequest $request,
        Comment $comment,
        StoreAttachmentService $service,
    ): JsonResponse {
        return (new AttachmentResource(
            $service->execute($comment, $request->file('file')),
        ))
            ->response()
            ->setStatusCode(201);
    }

    public function download(
        Comment $comment,
        Attachment $attachment,
        DownloadAttachmentService $service,
    ): StreamedResponse {
        $this->authorize('view', $comment->ticket);

        $download = $service->resolve($attachment);

        abort_unless($download !== null, 404);

        return Storage::disk($download->disk)->download(
            $download->path,
            $download->original_name,
        );
    }
}
