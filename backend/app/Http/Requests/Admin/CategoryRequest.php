<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $categoryId = $this->route('category');

        return [
            'parent_id' => [
                'nullable', 'integer',
                Rule::exists('categories', 'id')->whereNull('deleted_at'),
                Rule::notIn([$categoryId]),
            ],
            'name' => ['required', 'string', 'max:100'],
            'slug' => [
                'nullable', 'string', 'max:120',
                Rule::unique('categories', 'slug')
                    ->whereNull('deleted_at')
                    ->ignore($categoryId),
            ],
            'description' => ['nullable', 'string'],
            'image_path' => ['nullable', 'string', 'max:255'],
            'position' => ['nullable', 'integer', 'min:0'],
            'active' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! $this->slug && $this->name) {
            $this->merge(['slug' => Str::slug($this->name)]);
        }
    }
}
