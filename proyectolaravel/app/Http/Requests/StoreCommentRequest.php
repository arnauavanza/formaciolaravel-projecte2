<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $ticket = $this->route('ticket');

        return $user !== null
            && $ticket !== null
            && $user->can('comment', $ticket);
    }

    public function rules(): array
    {
        return [
            'body' => [
                'required',
                'string',
                'max:10000',
            ],
        ];
    }
}
