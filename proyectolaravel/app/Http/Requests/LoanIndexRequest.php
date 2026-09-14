<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LoanIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'active' => ['nullable', 'boolean'],
            'member_id' => [
                'nullable',
                'integer',
                'exists:members,id',
            ],
            'sort' => [
                'nullable',
                Rule::in([
                    'borrowed_at',
                    'due_at',
                    'returned_at',
                    'created_at',
                ]),
            ],
            'direction' => [
                'nullable',
                Rule::in(['asc', 'desc']),
            ],
            'per_page' => [
                'nullable',
                'integer',
                'min:1',
                'max:100',
            ],
        ];
    }
}
