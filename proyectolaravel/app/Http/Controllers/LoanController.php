<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoanIndexRequest;
use App\Http\Requests\StoreLoanRequest;
use App\Http\Requests\UpdateLoanRequest;
use App\Http\Resources\LoanResource;
use App\Models\Loan;

class LoanController extends Controller
{
    public function index(LoanIndexRequest $request)
    {
        $filters = $request->validated();

        $query = Loan::query()
            ->with([
                'member',
                'book.author',
                'book.genres',
            ]);

        if (array_key_exists('active', $filters)) {
            if (filter_var($filters['active'], FILTER_VALIDATE_BOOLEAN)) {
                $query->active();
            } else {
                $query->whereNotNull('returned_at');
            }
        }

        if (isset($filters['member_id'])) {
            $query->where('member_id', $filters['member_id']);
        }

        $loans = $query
            ->orderBy(
                $filters['sort'] ?? 'borrowed_at',
                $filters['direction'] ?? 'desc'
            )
            ->paginate($filters['per_page'] ?? 15);

        return LoanResource::collection($loans);
    }

    public function store(StoreLoanRequest $request)
    {
        $validated = $request->validated();

        if (
            is_null($validated['returned_at'] ?? null)
            && $this->hasActiveBookConflict($validated['book_id'])
        ) {
            return $this->activeBookConflictResponse();
        }

        $loan = Loan::create($validated);

        return (new LoanResource($this->loadRelations($loan)))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Loan $loan)
    {
        return new LoanResource($this->loadRelations($loan));
    }

    public function update(UpdateLoanRequest $request, Loan $loan)
    {
        $validated = $request->validated();

        if (
            is_null($validated['returned_at'] ?? null)
            && $this->hasActiveBookConflict($validated['book_id'], $loan->id)
        ) {
            return $this->activeBookConflictResponse();
        }

        $loan->update($validated);

        return new LoanResource(
            $this->loadRelations($loan->fresh())
        );
    }

    public function destroy(Loan $loan)
    {
        $loan->delete();

        return response()->noContent();
    }

    public function returnLoan(Loan $loan)
    {
        if ($loan->returned_at !== null) {
            return response()->json([
                'message' => 'This loan has already been returned.',
            ], 409);
        }

        $loan->update([
            'returned_at' => now(),
        ]);

        return new LoanResource(
            $this->loadRelations($loan->fresh())
        );
    }

    private function loadRelations(Loan $loan): Loan
    {
        return $loan->load([
            'member',
            'book.author',
            'book.genres',
        ]);
    }

    private function hasActiveBookConflict(int $bookId, ?int $ignoredLoanId = null): bool
    {
        return Loan::query()
            ->active()
            ->where('book_id', $bookId)
            ->when(
                $ignoredLoanId,
                fn ($query) => $query->where('id', '!=', $ignoredLoanId)
            )
            ->exists();
    }

    private function activeBookConflictResponse()
    {
        return response()->json([
            'message' => 'This book already has an active loan.',
        ], 409);
    }
}
