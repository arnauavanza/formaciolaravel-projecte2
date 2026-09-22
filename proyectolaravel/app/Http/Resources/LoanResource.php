<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LoanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'member_id' => $this->member_id,
            'book_id' => $this->book_id,
            'borrowed_at' => $this->borrowed_at?->toISOString(),
            'due_at' => $this->due_at?->toISOString(),
            'returned_at' => $this->returned_at?->toISOString(),
            'is_active' => is_null($this->returned_at),

            'member' => MemberResource::make(
                $this->whenLoaded('member')
            ),

            'book' => BookResource::make(
                $this->whenLoaded('book')
            ),
        ];
    }
}
