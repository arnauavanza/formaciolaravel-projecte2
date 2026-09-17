<?php

namespace App\Http\Controllers;

use App\Enums\TicketStatus;
use App\Http\Requests\StoreTicketRequest;
use App\Http\Requests\UpdateTicketRequest;
use App\Http\Resources\TicketResource;
use App\Models\Ticket;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Ticket::class);

        $query = Ticket::query()
            ->with(['customer', 'agent'])
            ->latest();

        if (! $request->user()->hasRole('admin')) {
            $userId = $request->user()->id;

            $query->where(function ($query) use ($userId) {
                $query
                    ->where('customer_id', $userId)
                    ->orWhere('agent_id', $userId);
            });
        }

        $tickets = $query->paginate(15);

        return TicketResource::collection($tickets);
    }

    public function store(StoreTicketRequest $request)
    {
        $this->authorize('create', Ticket::class);

        $validated = $request->validated();

        if (! $request->user()->hasRole('admin')) {
            $validated['customer_id'] = $request->user()->id;
        }

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
        $this->authorize('view', $ticket);

        return new TicketResource(
            $ticket->load(['customer', 'agent'])
        );
    }

    public function update(
        UpdateTicketRequest $request,
        Ticket $ticket
    ) {
        $this->authorize('update', $ticket);

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
        $this->authorize('delete', $ticket);

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
