<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLoanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'member_id' => ['required', 'integer', 'exists:members,id'],
            'book_id' => ['required', 'integer', 'exists:books,id'],
            'borrowed_at' => ['required', 'date'],
            'due_at' => ['required', 'date', 'after_or_equal:borrowed_at'],
            'returned_at' => ['nullable', 'date', 'after_or_equal:borrowed_at'],
        ];
    }
}
