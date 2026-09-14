<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Loan;
use App\Models\Member;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LoanApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_loans_can_be_listed(): void
    {
        $loan = Loan::factory()->active()->create();

        $this->getJson('/api/loans')
            ->assertOk()
            ->assertJsonPath('data.0.id', $loan->id)
            ->assertJsonPath('data.0.is_active', true);
    }

    public function test_loan_can_be_created(): void
    {
        $member = Member::factory()->create();
        $book = Book::factory()->create();

        $response = $this->postJson('/api/loans', [
            'member_id' => $member->id,
            'book_id' => $book->id,
            'borrowed_at' => now()->subDay()->toDateTimeString(),
            'due_at' => now()->addDays(13)->toDateTimeString(),
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.member_id', $member->id)
            ->assertJsonPath('data.book_id', $book->id)
            ->assertJsonPath('data.is_active', true);

        $this->assertDatabaseHas('loans', [
            'member_id' => $member->id,
            'book_id' => $book->id,
            'returned_at' => null,
        ]);
    }

    public function test_a_book_cannot_have_two_active_loans(): void
    {
        $existingLoan = Loan::factory()->active()->create();
        $newMember = Member::factory()->create();

        $this->postJson('/api/loans', [
            'member_id' => $newMember->id,
            'book_id' => $existingLoan->book_id,
            'borrowed_at' => now()->toDateTimeString(),
            'due_at' => now()->addDays(14)->toDateTimeString(),
        ])
            ->assertStatus(409);
    }

    public function test_loan_can_be_shown(): void
    {
        $loan = Loan::factory()->active()->create();

        $this->getJson("/api/loans/{$loan->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $loan->id)
            ->assertJsonPath('data.member_id', $loan->member_id)
            ->assertJsonPath('data.book_id', $loan->book_id);
    }

    public function test_loan_can_be_updated(): void
    {
        $loan = Loan::factory()->active()->create();
        $member = Member::factory()->create();
        $book = Book::factory()->create();

        $this->patchJson("/api/loans/{$loan->id}", [
            'member_id' => $member->id,
            'book_id' => $book->id,
            'borrowed_at' => now()->subDays(2)->toDateTimeString(),
            'due_at' => now()->addDays(12)->toDateTimeString(),
            'returned_at' => null,
        ])
            ->assertOk()
            ->assertJsonPath('data.member_id', $member->id)
            ->assertJsonPath('data.book_id', $book->id);

        $this->assertDatabaseHas('loans', [
            'id' => $loan->id,
            'member_id' => $member->id,
            'book_id' => $book->id,
        ]);
    }

    public function test_loan_can_be_deleted(): void
    {
        $loan = Loan::factory()->active()->create();

        $this->deleteJson("/api/loans/{$loan->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('loans', [
            'id' => $loan->id,
        ]);
    }

    public function test_loan_creation_is_validated(): void
    {
        $this->postJson('/api/loans', [
            'member_id' => 999999,
            'book_id' => 999999,
            'borrowed_at' => now()->toDateTimeString(),
            'due_at' => now()->subDay()->toDateTimeString(),
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'member_id',
                'book_id',
                'due_at',
            ]);
    }

    public function test_loans_can_be_filtered_by_active_status_and_member(): void
    {
        $member = Member::factory()->create();

        $activeLoan = Loan::factory()->active()->create([
            'member_id' => $member->id,
        ]);

        Loan::factory()->returned()->create([
            'member_id' => $member->id,
        ]);

        $this->getJson("/api/loans?active=1&member_id={$member->id}")
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', $activeLoan->id);
    }

    public function test_loans_can_be_sorted_and_paginated(): void
    {
        Loan::factory()->active()->create([
            'borrowed_at' => now()->subDays(10),
        ]);

        $newestLoan = Loan::factory()->active()->create([
            'borrowed_at' => now()->subDay(),
        ]);

        $this->getJson('/api/loans?sort=borrowed_at&direction=desc&per_page=1')
            ->assertOk()
            ->assertJsonPath('data.0.id', $newestLoan->id)
            ->assertJsonPath('meta.per_page', 1);
    }

    public function test_loan_filters_are_validated(): void
    {
        $this->getJson('/api/loans?member_id=999999&sort=invalid')
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'member_id',
                'sort',
            ]);
    }

    public function test_active_loan_can_be_returned(): void
    {
        $loan = Loan::factory()->active()->create();

        $this->postJson("/api/loans/{$loan->id}/return")
            ->assertOk()
            ->assertJsonPath('data.is_active', false);

        $this->assertNotNull($loan->fresh()->returned_at);
    }

    public function test_already_returned_loan_cannot_be_returned_again(): void
    {
        $loan = Loan::factory()->returned()->create();

        $this->postJson("/api/loans/{$loan->id}/return")
            ->assertStatus(409);
    }

    public function test_nonexistent_loan_returns_not_found(): void
    {
        $this->postJson('/api/loans/999999/return')
            ->assertNotFound();
    }

    public function test_loan_index_avoids_n_plus_one_queries(): void
    {
        Loan::factory()->count(5)->active()->create();

        $queryCount = 0;

        DB::listen(function (QueryExecuted $query) use (&$queryCount): void {
            $queryCount++;
        });

        $this->getJson('/api/loans')
            ->assertOk();

        $this->assertLessThanOrEqual(7, $queryCount);
    }
}
