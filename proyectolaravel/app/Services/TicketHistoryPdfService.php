<?php

namespace App\Services;

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

    public function temporaryUrl(Ticket $ticket): ?string
    {
        $path = $this->path($ticket);
        $disk = Storage::disk('local');

        if (! $disk->exists($path)) {
            return null;
        }

        return $disk->temporaryUrl(
            $path,
            now()->addMinutes(5)
        );
    }

    private function path(Ticket $ticket): string
    {
        return "tickets/{$ticket->id}/history.pdf";
    }
}
