<?php

namespace App\Services;

use App\Exceptions\DomainRuleException;
use App\Models\Loan;

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
            throw new DomainRuleException(
                'This book already has an active loan.',
                409,
            );
        }

        $loan->update($attributes);

        return $this->loans->withRelations($loan->fresh());
    }
}
