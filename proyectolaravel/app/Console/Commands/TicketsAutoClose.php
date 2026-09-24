<?php

namespace App\Console\Commands;

use App\Enums\TicketStatus;
use App\Exceptions\DomainRuleException;
use App\Models\Ticket;
use App\Services\CloseTicketService;
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
                now()->subDays(config('tickets.auto_close_days'))
            );

        $dryRun = $this->option('dry-run');
        $found = 0;
        $closed = 0;

        foreach ($tickets->lazyById() as $ticket) {
            $found++;

            if ($dryRun) {
                $this->line("Would close ticket #{$ticket->id}.");

                continue;
            }

            try {
                $this->closeTicket->execute($ticket);
                $closed++;
            } catch (DomainRuleException $exception) {
                $this->error(
                    "Ticket #{$ticket->id}: {$exception->getMessage()}"
                );
            }
        }

        if ($found === 0) {
            $this->info('No tickets require automatic closure.');

            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->info("{$found} ticket(s) would be closed.");

            return self::SUCCESS;
        }

        $this->info("Closed {$closed} ticket(s).");

        return self::SUCCESS;
    }
}
