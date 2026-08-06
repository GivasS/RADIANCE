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
            // Sem a regra "image" pois ela nao reconhece heic/heif (fotos
            // padrao do iPhone) - "mimes" ja valida o conteudo real do
            // arquivo, nao so a extensao, entao continua seguro.
            'images' => ['required', 'array', 'min:1'],
            'images.*' => ['file', 'mimes:jpg,jpeg,png,webp,heic,heif', 'max:8192'], // 8MB
            'alt_text' => ['nullable', 'string', 'max:255'],
        ];
    }
}
