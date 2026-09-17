<?php

namespace App\Http\Controllers;

use App\Enums\TicketStatus;
use App\Http\Requests\StoreTicketRequest;
use App\Http\Requests\UpdateTicketRequest;
use App\Http\Resources\TicketResource;
use App\Models\Ticket;

class TicketController extends Controller
{
    public function index()
    {
        $tickets = Ticket::query()
            ->with(['customer', 'agent'])
            ->latest()
            ->paginate(15);

        return TicketResource::collection($tickets);
    }

    public function store(StoreTicketRequest $request)
    {
        $validated = $request->validated();

        $ticket = Ticket::create([
            ...$validated,
            'status' => TicketStatus::Open,
            'last_activity_at' => now(),
        ]);

        return (new TicketResource($ticket))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Ticket $ticket)
    {
        return new TicketResource(
            $ticket->load(['customer', 'agent'])
        );
    }

    public function update(
        UpdateTicketRequest $request,
        Ticket $ticket
    ) {
        $validated = $request->validated();

        if (array_key_exists('status', $validated)) {
            $nextStatus = TicketStatus::from($validated['status']);

            if (! $this->canTransition($ticket->status, $nextStatus)) {
                return response()->json([
                    'message' => 'Invalid ticket status transition.',
                ], 422);
            }

            $validated['status'] = $nextStatus;

            if ($nextStatus === TicketStatus::Resolved) {
                $validated['resolved_at'] = now();
            }

            if ($nextStatus === TicketStatus::Closed) {
                $validated['resolved_at'] ??= now();
                $validated['closed_at'] = now();
            }
        }

        $validated['last_activity_at'] = now();

        $ticket->update($validated);

        return new TicketResource(
            $ticket->fresh(['customer', 'agent'])
        );
    }

    public function destroy(Ticket $ticket)
    {
        $ticket->delete();

        return response()->noContent();
    }

    private function canTransition(
        TicketStatus $currentStatus,
        TicketStatus $nextStatus
    ): bool {
        if ($currentStatus === $nextStatus) {
            return true;
        }

        return match ($currentStatus) {
            TicketStatus::Open => $nextStatus === TicketStatus::InProgress,
            TicketStatus::InProgress => $nextStatus === TicketStatus::Resolved,
            TicketStatus::Resolved => $nextStatus === TicketStatus::Closed,
            TicketStatus::Closed => false,
        };
    }
}
