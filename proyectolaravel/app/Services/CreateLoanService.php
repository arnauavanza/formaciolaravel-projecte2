<?php

namespace App\Services;

use App\Exceptions\DomainRuleException;
use App\Models\Loan;

class CreateLoanService
{
    public function __construct(
        private LoanQueryService $loans
    ) {}

    public function execute(array $attributes): Loan
    {
        if (
            is_null($attributes['returned_at'] ?? null)
            && $this->loans->hasActiveBookConflict($attributes['book_id'])
        ) {
            throw new DomainRuleException(
                'This book already has an active loan.',
                409,
            );
        }

        return $this->loans->withRelations(
            Loan::create($attributes)
        );
    }
}
