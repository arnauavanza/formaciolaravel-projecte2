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
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class LoanController extends Controller
{
    public function index(
        LoanIndexRequest $request,
        ListLoansService $service,
    ): AnonymousResourceCollection {
        return LoanResource::collection(
            $service->execute($request->validated()),
        );
    }

    public function store(
        StoreLoanRequest $request,
        CreateLoanService $service,
    ): JsonResponse {
        return (new LoanResource(
            $service->execute($request->validated()),
        ))->response()->setStatusCode(201);
    }

    public function show(Loan $loan): LoanResource
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
        UpdateLoanService $service,
    ): LoanResource {
        return new LoanResource(
            $service->execute($loan, $request->validated()),
        );
    }

    public function destroy(
        Loan $loan,
        DeleteLoanService $service,
    ): Response {
        $service->execute($loan);

        return response()->noContent();
    }

    public function returnLoan(
        Loan $loan,
        ReturnLoanService $service,
    ): LoanResource {
        return new LoanResource(
            $service->execute($loan),
        );
    }
}
