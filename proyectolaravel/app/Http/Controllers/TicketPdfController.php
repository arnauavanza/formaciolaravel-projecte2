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

        return $service->download($ticket);
    }
}
