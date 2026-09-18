<?php

namespace Tests\Feature;

use App\Enums\TicketStatus;
use App\Jobs\GenerateTicketHistoryPdf;
use App\Jobs\SendTicketResolvedMail;
use App\Mail\TicketResolvedMail;
use App\Models\Ticket;
use App\Services\TicketHistoryPdfService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TicketPdfJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_pdf_job_stores_pdf_and_dispatches_resolved_mail(): void
    {
        Storage::fake('local');
        Queue::fake();

        $ticket = Ticket::factory()->closed()->create([
            'status' => TicketStatus::Closed,
        ]);

        (new GenerateTicketHistoryPdf($ticket->id))
            ->handle(app(TicketHistoryPdfService::class));

        $path = "tickets/{$ticket->id}/history.pdf";

        Storage::disk('local')->assertExists($path);

        Queue::assertPushed(
            SendTicketResolvedMail::class,
            fn (SendTicketResolvedMail $job): bool => $job->ticketId === $ticket->id
                && $job->pdfPath === $path,
        );
    }

    public function test_resolved_mail_job_sends_mail_with_pdf_path(): void
    {
        Storage::fake('local');
        Mail::fake();

        $ticket = Ticket::factory()->closed()->create([
            'status' => TicketStatus::Closed,
        ]);

        $path = "tickets/{$ticket->id}/history.pdf";

        Storage::disk('local')->put($path, 'fake-pdf');

        (new SendTicketResolvedMail(
            $ticket->id,
            $path,
        ))->handle();

        Mail::assertSent(
            TicketResolvedMail::class,
            fn (TicketResolvedMail $mail): bool => $mail->ticket->id === $ticket->id
                && $mail->pdfPath === $path,
        );
    }
}
