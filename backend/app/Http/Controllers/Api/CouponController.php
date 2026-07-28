<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CartService;
use App\Services\CouponService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    public function __construct(
        private readonly CouponService $coupons,
        private readonly CartService $carts,
    ) {}

    public function validateCode(Request $request): JsonResponse
    {
        $request->validate(['code' => ['required', 'string']]);

        $cart = $request->attributes->get('cart');
        $subtotal = $cart ? $this->carts->present($cart)['subtotal'] : 0.0;

        $result = $this->coupons->validate($request->string('code'), $subtotal, $request->user());

        return response()->json([
            'coupon' => [
                'code' => $result['coupon']->code,
                'type' => $result['coupon']->type,
                'value' => (float) $result['coupon']->value,
            ],
            'discount' => $result['discount'],
        ]);
    }
}
