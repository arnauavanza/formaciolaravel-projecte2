<?php

namespace App\Services;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class TicketHistoryPdfService
{
    public function generate(Ticket $ticket): string
    {
        $ticket->load([
            'customer',
            'agent',
            'comments.user',
            'comments.attachments',
        ]);

        return Pdf::loadView(
            'pdf.tickets.history',
            ['ticket' => $ticket],
        )->output();
    }

    public function download(Ticket $ticket)
    {
        if ($ticket->status !== TicketStatus::Closed) {
            return response()->json([
                'message' => 'The PDF is available after the ticket is closed.',
            ], 409);
        }

        $path = $this->path($ticket);
        $disk = Storage::disk('local');

        if (! $disk->exists($path)) {
            return response()->json([
                'message' => 'The PDF is still being generated.',
            ], 409);
        }

        return redirect()->away(
            $disk->temporaryUrl(
                $path,
                now()->addMinutes(5)
            )
        );
    }

    private function path(Ticket $ticket): string
    {
        return "tickets/{$ticket->id}/history.pdf";
    }
}
