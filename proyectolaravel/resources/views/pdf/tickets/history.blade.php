<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Ticket {{ $ticket->id }} history</title>

    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #222;
        }

        h1 {
            font-size: 22px;
            margin-bottom: 8px;
        }

        h2 {
            font-size: 16px;
            margin-top: 24px;
        }

        .comment {
            border-top: 1px solid #ccc;
            padding: 10px 0;
        }

        .muted {
            color: #666;
            font-size: 10px;
        }
    </style>
</head>
<body>
    <h1>Ticket #{{ $ticket->id }}</h1>

    <p><strong>Title:</strong> {{ $ticket->title }}</p>
    <p><strong>Status:</strong> {{ $ticket->status->value }}</p>
    <p><strong>Customer:</strong> {{ $ticket->customer->email }}</p>

    @if ($ticket->agent)
        <p><strong>Agent:</strong> {{ $ticket->agent->email }}</p>
    @endif

    <h2>Description</h2>

    <p>{{ $ticket->description }}</p>

    <h2>Comments</h2>

    @forelse ($ticket->comments as $comment)
        <div class="comment">
            <strong>{{ $comment->user->name }}</strong>

            <div class="muted">
                {{ $comment->created_at?->toDateTimeString() }}
            </div>

            <p>{{ $comment->body }}</p>

            @if ($comment->attachments->isNotEmpty())
                <p class="muted">
                    Attachments:
                    {{ $comment->attachments->pluck('original_name')->join(', ') }}
                </p>
            @endif
        </div>
    @empty
        <p>No comments.</p>
    @endforelse
</body>
</html>
