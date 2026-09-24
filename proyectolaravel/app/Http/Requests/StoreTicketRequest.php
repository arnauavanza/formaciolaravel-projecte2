<?php

namespace App\Http\Requests;

use App\Models\Ticket;
use Illuminate\Foundation\Http\FormRequest;

class StoreTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->user()->can('createForAnotherUser', Ticket::class)) {
            $this->merge([
                'customer_id' => $this->user()->id,
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'title' => [
                'required',
                'string',
                'max:255',
            ],
            'description' => [
                'required',
                'string',
            ],
            'customer_id' => [
                'required',
                'integer',
                'exists:users,id',
            ],
            'agent_id' => [
                'nullable',
                'integer',
                'exists:users,id',
            ],
        ];
    }
}
