<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Services\TicketHistoryPdfService;

class TicketPdfController extends Controller
{
    public function download(
        Ticket $ticket,
        TicketHistoryPdfService $service
    ) {
        $this->authorize('view', $ticket);

        $filename = "ticket-{$ticket->id}-history.pdf";

        return response(
            $service->generate($ticket),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            ],
        );
    }
}
