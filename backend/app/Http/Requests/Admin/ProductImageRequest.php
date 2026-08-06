<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ProductImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // "image" no Laravel ja valida o conteudo real do arquivo (nao so a extensao).
            'images' => ['required', 'array', 'min:1'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:8192'], // 8MB
            'alt_text' => ['nullable', 'string', 'max:255'],
        ];
    }
}
