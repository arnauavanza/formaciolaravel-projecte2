<?php

namespace App\Services;

use App\Models\Loan;
use DomainException;

class UpdateLoanService
{
    public function __construct(
        private LoanQueryService $loans
    ) {}

    public function execute(Loan $loan, array $attributes): Loan
    {
        if (
            is_null($attributes['returned_at'] ?? null)
            && $this->loans->hasActiveBookConflict(
                $attributes['book_id'],
                $loan->id
            )
        ) {
            throw new DomainException(
                'This book already has an active loan.'
            );
        }

        $loan->update($attributes);

        return $this->loans->withRelations($loan->fresh());
    }
}
