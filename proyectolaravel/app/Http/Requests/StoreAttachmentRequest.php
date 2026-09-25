<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAttachmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $comment = $this->route('comment');

        return $user !== null
            && $comment !== null
            && $comment->user_id === $user->id
            && $user->can('comment', $comment->ticket);
    }

    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'mimes:jpg,jpeg,png,pdf,txt,doc,docx',
                'max:10240',
            ],
        ];
    }
}
