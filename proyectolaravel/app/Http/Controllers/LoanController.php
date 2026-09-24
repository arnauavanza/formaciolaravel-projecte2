<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoanIndexRequest;
use App\Http\Requests\StoreLoanRequest;
use App\Http\Requests\UpdateLoanRequest;
use App\Http\Resources\LoanResource;
use App\Models\Loan;
use App\Services\CreateLoanService;
use App\Services\DeleteLoanService;
use App\Services\ListLoansService;
use App\Services\ReturnLoanService;
use App\Services\UpdateLoanService;

class LoanController extends Controller
{
    public function index(
        LoanIndexRequest $request,
        ListLoansService $service
    ) {
        return LoanResource::collection(
            $service->execute($request->validated())
        );
    }

    public function store(
        StoreLoanRequest $request,
        CreateLoanService $service
    ) {
        return (new LoanResource(
            $service->execute($request->validated())
        ))->response()->setStatusCode(201);
    }

    public function show(Loan $loan)
    {
        return new LoanResource($loan->load([
            'member',
            'book.author',
            'book.genres',
        ]));
    }

    public function update(
        UpdateLoanRequest $request,
        Loan $loan,
        UpdateLoanService $service
    ) {
        return new LoanResource(
            $service->execute($loan, $request->validated())
        );
    }

    public function destroy(
        Loan $loan,
        DeleteLoanService $service
    ) {
        $service->execute($loan);

        return response()->noContent();
    }

    public function returnLoan(
        Loan $loan,
        ReturnLoanService $service
    ) {
        return new LoanResource(
            $service->execute($loan)
        );
    }
}
