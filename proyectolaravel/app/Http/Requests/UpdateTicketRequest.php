<?php

namespace App\Http\Requests;

use App\Enums\TicketStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $ticket = $this->route('ticket');

        return $user !== null
            && $ticket !== null
            && $user->can('update', $ticket);
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
                Rule::notIn([TicketStatus::Closed->value]),
            ],
        ];
    }
}
