<?php

namespace App\Services;

use App\Exceptions\DomainRuleException;
use App\Models\Book;

class DeleteBookService
{
    public function execute(Book $book): void
    {
        if ($book->loans()->exists()) {
            throw new DomainRuleException(
                'Cannot delete a book with loan history.',
                409,
            );
        }

        $book->delete();
    }
}
