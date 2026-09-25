<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssignTicketRequest;
use App\Http\Requests\StoreTicketRequest;
use App\Http\Requests\UpdateTicketRequest;
use App\Http\Resources\TicketResource;
use App\Models\Ticket;
use App\Repositories\Contracts\TicketRepositoryInterface;
use App\Services\AssignTicketService;
use App\Services\CloseTicketService;
use App\Services\CreateTicketService;
use App\Services\ListTicketsService;
use App\Services\UpdateTicketService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class TicketController extends Controller
{
    public function __construct(
        private readonly TicketRepositoryInterface $tickets,
    ) {}

    public function index(
        Request $request,
        ListTicketsService $service,
    ): AnonymousResourceCollection {
        $this->authorize('viewAny', Ticket::class);

        return TicketResource::collection(
            $service->execute($request->user()),
        );
    }

    public function store(
        StoreTicketRequest $request,
        CreateTicketService $service,
    ): JsonResponse {
        return (new TicketResource(
            $service->execute($request->validated()),
        ))->response()->setStatusCode(201);
    }

    public function show(Ticket $ticket): TicketResource
    {
        $this->authorize('view', $ticket);

        return new TicketResource($ticket);
    }

    public function assign(
        AssignTicketRequest $request,
        Ticket $ticket,
        AssignTicketService $service,
    ): TicketResource {
        return new TicketResource(
            $service->execute($ticket, $request->validated('agent_id')),
        );
    }

    public function close(
        Ticket $ticket,
        CloseTicketService $service,
    ): TicketResource {
        $this->authorize('close', $ticket);

        return new TicketResource($service->execute($ticket));
    }

    public function update(
        UpdateTicketRequest $request,
        Ticket $ticket,
        UpdateTicketService $service,
    ): TicketResource {
        return new TicketResource(
            $service->execute($ticket, $request->validated()),
        );
    }

    public function destroy(Ticket $ticket): Response
    {
        $this->authorize('delete', $ticket);
        $this->tickets->delete($ticket);

        return response()->noContent();
    }
}
