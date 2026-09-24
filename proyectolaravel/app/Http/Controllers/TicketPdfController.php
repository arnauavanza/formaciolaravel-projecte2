<?php

namespace App\Http\Controllers;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Services\TicketHistoryPdfService;

class TicketPdfController extends Controller
{
    public function download(
        Ticket $ticket,
        TicketHistoryPdfService $service
    ) {
        $this->authorize('view', $ticket);

        if ($ticket->status !== TicketStatus::Closed) {
            return response()->json([
                'message' => 'The PDF is available after the ticket is closed.',
            ], 409);
        }

        $url = $service->temporaryUrl($ticket);

        if ($url === null) {
            return response()->json([
                'message' => 'The PDF is still being generated.',
            ], 409);
        }

        return redirect()->away($url);
    }
}
