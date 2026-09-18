<?php

namespace App\Console\Commands;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Services\CloseTicketService;
use DomainException;
use Illuminate\Console\Command;

class TicketsAutoClose extends Command
{
    protected $signature = 'tickets:auto-close
        {--dry-run : Show tickets without closing them}';

    protected $description = 'Close resolved tickets inactive for seven days';

    public function __construct(
        private CloseTicketService $closeTicket
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $tickets = Ticket::query()
            ->where('status', TicketStatus::Resolved->value)
            ->where(
                'last_activity_at',
                '<=',
                now()->subDays(7)
            )
            ->get();

        if ($tickets->isEmpty()) {
            $this->info('No tickets require automatic closure.');

            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            foreach ($tickets as $ticket) {
                $this->line(
                    "Would close ticket #{$ticket->id}."
                );
            }

            $this->info(
                "{$tickets->count()} ticket(s) would be closed."
            );

            return self::SUCCESS;
        }

        $closed = 0;

        foreach ($tickets as $ticket) {
            try {
                $this->closeTicket->execute($ticket);
                $closed++;
            } catch (DomainException $exception) {
                $this->error(
                    "Ticket #{$ticket->id}: {$exception->getMessage()}"
                );
            }
        }

        $this->info("Closed {$closed} ticket(s).");

        return self::SUCCESS;
    }
}
