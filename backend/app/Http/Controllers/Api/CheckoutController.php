<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Checkout\CheckoutRequest;
use App\Services\CheckoutService;
use Illuminate\Http\JsonResponse;

class CheckoutController extends Controller
{
    public function __construct(private readonly CheckoutService $checkout) {}

    public function store(CheckoutRequest $request): JsonResponse
    {
        $cart = $request->attributes->get('cart');

        $result = $this->checkout->checkout(
            user: $request->user(),
            cart: $cart,
            addressId: $request->integer('address_id'),
            shippingRateId: $request->integer('shipping_rate_id'),
            couponCode: $request->string('coupon_code')->value() ?: null,
            paymentMethod: $request->string('payment_method')->value(),
            card: $request->input('card'),
        );

        return response()->json([
            'order' => $result['order'],
            'payment' => $result['payment'],
        ], 201);
    }
}
