<?php

namespace App\Services;

use App\Models\Loan;

class LoanQueryService
{
    public function withRelations(Loan $loan): Loan
    {
        return $loan->load([
            'member',
            'book.author',
            'book.genres',
        ]);
    }

    public function hasActiveBookConflict(
        int $bookId,
        ?int $ignoredLoanId = null
    ): bool {
        return Loan::query()
            ->active()
            ->where('book_id', $bookId)
            ->when(
                $ignoredLoanId,
                fn ($query) => $query->where('id', '!=', $ignoredLoanId)
            )
            ->exists();
    }
}
