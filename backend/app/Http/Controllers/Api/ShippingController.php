<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CartService;
use App\Services\ShippingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShippingController extends Controller
{
    public function __construct(
        private readonly ShippingService $shipping,
        private readonly CartService $carts,
    ) {}

    public function quote(Request $request): JsonResponse
    {
        $request->validate([
            'zipcode' => ['required', 'string', 'min:8'],
            'subtotal' => ['nullable', 'numeric', 'min:0'],
        ]);

        // Se vier um subtotal explicito (ex: calculo na pagina do produto), usa ele.
        // Senao, calcula em cima do carrinho atual (ex: calculo no checkout).
        if ($request->filled('subtotal')) {
            $subtotal = $request->float('subtotal');
        } else {
            $cart = $request->attributes->get('cart');
            $subtotal = $cart ? $this->carts->present($cart)['subtotal'] : 0.0;
        }

        $options = $this->shipping->quote($request->string('zipcode'), $subtotal);

        return response()->json(['options' => $options]);
    }
}
