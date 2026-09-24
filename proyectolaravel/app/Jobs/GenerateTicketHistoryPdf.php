<?php

namespace App\Jobs;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Services\TicketHistoryPdfService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;

class GenerateTicketHistoryPdf implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 60;

    public int $uniqueFor = 3600;

    public function __construct(
        public int $ticketId
    ) {}

    public function handle(TicketHistoryPdfService $service): void
    {
        $ticket = Ticket::query()->find($this->ticketId);

        if (
            $ticket === null
            || $ticket->status !== TicketStatus::Closed
        ) {
            return;
        }

        $path = "tickets/{$ticket->id}/history.pdf";
        $disk = Storage::disk('local');

        if (! $disk->exists($path)) {
            $disk->put(
                $path,
                $service->generate($ticket),
            );
        }

        SendTicketResolvedMail::dispatch(
            $ticket->id,
            $path,
        )->afterCommit();
    }

    public function uniqueId(): string
    {
        return "ticket-history-pdf:{$this->ticketId}";
    }
}
