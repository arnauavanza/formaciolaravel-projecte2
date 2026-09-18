<?php

namespace App\Http\Controllers;

use App\Enums\TicketStatus;
use App\Http\Requests\AssignTicketRequest;
use App\Http\Requests\StoreTicketRequest;
use App\Http\Requests\UpdateTicketRequest;
use App\Http\Resources\TicketResource;
use App\Models\Ticket;
use App\Models\User;
use App\Repositories\Contracts\TicketRepositoryInterface;
use App\Services\AssignTicketService;
use App\Services\CloseTicketService;
use App\Services\UpdateTicketService;
use DomainException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class TicketController extends Controller
{
    public function __construct(
        private TicketRepositoryInterface $tickets
    ) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Ticket::class);

        $tickets = $this->tickets->paginateVisibleTo(
            $request->user(),
            Gate::allows('viewAll', Ticket::class),
        );

        return TicketResource::collection($tickets);
    }

    public function store(StoreTicketRequest $request)
    {
        $this->authorize('create', Ticket::class);

        $validated = $request->validated();

        if (! Gate::allows('createForAnotherUser', Ticket::class)) {
            $validated['customer_id'] = $request->user()->id;
        }

        $ticket = $this->tickets->create([
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

    public function assign(
        AssignTicketRequest $request,
        Ticket $ticket,
        AssignTicketService $service
    ) {
        $this->authorize('assign', $ticket);

        $agent = User::query()->findOrFail(
            $request->validated('agent_id')
        );

        try {
            $ticket = $service->execute($ticket, $agent);
        } catch (DomainException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }

        return new TicketResource($ticket);
    }

    public function close(
        Ticket $ticket,
        CloseTicketService $service
    ) {
        $this->authorize('close', $ticket);

        try {
            $ticket = $service->execute($ticket);
        } catch (DomainException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }

        return new TicketResource($ticket);
    }

    public function update(
        UpdateTicketRequest $request,
        Ticket $ticket,
        UpdateTicketService $service
    ) {
        $this->authorize('update', $ticket);

        try {
            $ticket = $service->execute(
                $ticket,
                $request->validated()
            );
        } catch (DomainException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }

        return new TicketResource($ticket);
    }

    public function destroy(Ticket $ticket)
    {
        $this->authorize('delete', $ticket);

        $this->tickets->delete($ticket);

        return response()->noContent();
    }
}
