<?php

namespace App\Services;

use App\Models\Loan;

class DeleteLoanService
{
    public function execute(Loan $loan): void
    {
        $loan->delete();
    }
}
