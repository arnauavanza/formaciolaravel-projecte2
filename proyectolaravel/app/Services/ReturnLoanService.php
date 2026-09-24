<?php

namespace App\Services;

use App\Models\Loan;
use DomainException;

class ReturnLoanService
{
    public function __construct(
        private LoanQueryService $loans
    ) {}

    public function execute(Loan $loan): Loan
    {
        if ($loan->returned_at !== null) {
            throw new DomainException(
                'This loan has already been returned.'
            );
        }

        $loan->update([
            'returned_at' => now(),
        ]);

        return $this->loans->withRelations($loan->fresh());
    }
}
