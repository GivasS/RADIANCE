<?php

namespace App\Http\Requests\Checkout;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'address_id' => ['required', 'integer'],
            'shipping_rate_id' => ['required', 'integer'],
            'coupon_code' => ['nullable', 'string'],
            'payment_method' => ['required', Rule::in(['pix', 'cartao'])],

            'card.payment_token' => ['required_if:payment_method,cartao', 'string'],
            'card.installments' => ['required_if:payment_method,cartao', 'integer', 'min:1', 'max:12'],
            'card.holder_document' => ['required_if:payment_method,cartao', 'string'],
            'card.holder_birth' => ['required_if:payment_method,cartao', 'date'],
        ];
    }
}
