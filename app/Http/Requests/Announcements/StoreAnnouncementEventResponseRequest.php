<?php

namespace App\Http\Requests\Announcements;

use Illuminate\Foundation\Http\FormRequest;

class StoreAnnouncementEventResponseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'response' => ['required', 'string', 'in:yes,no,maybe'],
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'response.required' => 'Please select an RSVP response.',
            'response.in' => 'RSVP response must be Yes, No, or Maybe.',
        ];
    }
}
