<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in([
                'pendente', 'aguardando_pagamento', 'pago', 'separando',
                'enviado', 'entregue', 'cancelado', 'expirado', 'estornado',
            ])],
        ];
    }
}
