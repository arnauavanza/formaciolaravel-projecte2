<?php

use App\Http\Controllers\AuthorController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\GenreController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\MemberController;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'service' => 'library-api',
    ]);
})->name('api.health');

Route::apiResource('authors', AuthorController::class);
Route::apiResource('books', BookController::class);
Route::get('genres', [GenreController::class, 'index'])
    ->name('genres.index');
Route::get('loans', [LoanController::class, 'index'])
    ->name('loans.index');
Route::post('loans', [LoanController::class, 'store'])
    ->name('loans.store');
Route::get('loans/{loan}', [LoanController::class, 'show'])
    ->name('loans.show');
Route::match(['put', 'patch'], 'loans/{loan}', [LoanController::class, 'update'])
    ->name('loans.update');
Route::delete('loans/{loan}', [LoanController::class, 'destroy'])
    ->name('loans.destroy');
Route::post('loans/{loan}/return', [LoanController::class, 'returnLoan'])
    ->name('loans.return');
Route::apiResource('members', MemberController::class);
