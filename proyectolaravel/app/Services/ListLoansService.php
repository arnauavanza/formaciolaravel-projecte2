<?php

namespace App\Services;

use App\Models\Loan;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListLoansService
{
    public function execute(array $filters): LengthAwarePaginator
    {
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

        return $query
            ->orderBy(
                $filters['sort'] ?? 'borrowed_at',
                $filters['direction'] ?? 'desc'
            )
            ->paginate($filters['per_page'] ?? 15);
    }
}
