<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class StoreSavedAccountLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255'],
            'device_id' => ['required', 'string', 'max:100'],
            'selector' => ['required', 'uuid'],
            'token' => ['required', 'string', 'max:255'],
        ];
    }
}
