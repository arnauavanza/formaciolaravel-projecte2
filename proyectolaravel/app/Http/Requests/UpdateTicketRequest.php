<?php

namespace App\Http\Requests;

use App\Enums\TicketStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => [
                'sometimes',
                'string',
                'max:255',
            ],
            'description' => [
                'sometimes',
                'string',
            ],
            'status' => [
                'sometimes',
                Rule::enum(TicketStatus::class),
            ],
            'agent_id' => [
                'sometimes',
                'nullable',
                'integer',
                'exists:users,id',
            ],
        ];
    }
}
