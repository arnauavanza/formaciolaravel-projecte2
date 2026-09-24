<?php

namespace App\Services;

use App\Models\Loan;
use DomainException;

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
            throw new DomainException(
                'This book already has an active loan.'
            );
        }

        return $this->loans->withRelations(
            Loan::create($attributes)
        );
    }
}
