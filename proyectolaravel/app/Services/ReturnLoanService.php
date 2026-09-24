<?php

namespace App\Services;

use App\Exceptions\DomainRuleException;
use App\Models\Loan;

class ReturnLoanService
{
    public function __construct(
        private LoanQueryService $loans
    ) {}

    public function execute(Loan $loan): Loan
    {
        if ($loan->returned_at !== null) {
            throw new DomainRuleException(
                'This loan has already been returned.',
                409,
            );
        }

        $loan->update([
            'returned_at' => now(),
        ]);

        return $this->loans->withRelations($loan->fresh());
    }
}
