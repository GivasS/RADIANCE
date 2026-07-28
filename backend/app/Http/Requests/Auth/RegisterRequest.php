<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'email' => [
                'required', 'string', 'email', 'max:150',
                Rule::unique('users', 'email')->where(fn ($q) => $q->whereNull('deleted_at')),
            ],
            'cpf' => [
                'required', 'string', 'max:14',
                Rule::unique('users', 'cpf')->where(fn ($q) => $q->whereNull('deleted_at')),
            ],
            'phone' => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ];
    }
}
