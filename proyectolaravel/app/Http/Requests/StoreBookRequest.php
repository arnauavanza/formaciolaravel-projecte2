<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'author_id' => ['required', 'integer', 'exists:authors,id'],
            'title' => ['required', 'string', 'max:255'],
            'isbn' => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('books', 'isbn'),
            ],
            'published_year' => [
                'nullable',
                'integer',
                'min:0',
                'max:'.date('Y'),
            ],
            'genre_ids' => ['sometimes', 'array'],
            'genre_ids.*' => [
                'integer',
                'distinct',
                'exists:genres,id',
            ],
        ];
    }
}
