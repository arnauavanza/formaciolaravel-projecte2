<?php

namespace App\Services;

use App\Models\Ticket;
use Barryvdh\DomPDF\Facade\Pdf;

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
}
