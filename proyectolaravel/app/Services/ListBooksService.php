<?php

namespace App\Services;

use App\Models\Book;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListBooksService
{
    public function execute(array $filters): LengthAwarePaginator
    {
        return Book::query()
            ->with(['author', 'genres'])
            ->orderBy(
                $filters['sort'] ?? 'title',
                $filters['direction'] ?? 'asc'
            )
            ->paginate($filters['per_page'] ?? 15);
    }
}
