<?php

use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AuthorController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\GenreController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\TicketPdfController;
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
Route::middleware([
    'auth:sanctum',
    'active.user',
    'log.authenticated',
])->group(function () {
    Route::get('auth/me', [AuthController::class, 'me'])
        ->name('auth.me');

    Route::post('auth/logout', [AuthController::class, 'logout'])
        ->name('auth.logout');

    Route::post('tickets/{ticket}/assign', [TicketController::class, 'assign'])
        ->name('tickets.assign');

    Route::post('tickets/{ticket}/close', [TicketController::class, 'close'])
        ->name('tickets.close');

    Route::get(
        'tickets/{ticket}/pdf',
        [TicketPdfController::class, 'download']
    )->name('tickets.pdf.download');

    Route::post(
        'comments/{comment}/attachments',
        [AttachmentController::class, 'store']
    )->name('comments.attachments.store');

    Route::get(
        'comments/{comment}/attachments/{attachment}/download',
        [AttachmentController::class, 'download']
    )->name('comments.attachments.download');

    Route::apiResource('tickets', TicketController::class);

    Route::get(
        'tickets/{ticket}/comments',
        [CommentController::class, 'index']
    )->name('tickets.comments.index');

    Route::post(
        'tickets/{ticket}/comments',
        [CommentController::class, 'store']
    )->name('tickets.comments.store');
});
Route::post('auth/register', [AuthController::class, 'register'])
    ->name('auth.register');

Route::post('auth/login', [AuthController::class, 'login'])
    ->name('auth.login');
