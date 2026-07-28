<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cart\AddCartItemRequest;
use App\Http\Requests\Cart\UpdateCartItemRequest;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(private readonly CartService $carts) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json($this->carts->present($this->currentCart($request)));
    }

    public function storeItem(AddCartItemRequest $request): JsonResponse
    {
        $cart = $this->currentCart($request);
        $product = Product::findOrFail($request->integer('product_id'));
        $variant = ProductVariant::findOrFail($request->integer('variant_id'));

        $this->carts->addItem($cart, $product, $variant, $request->integer('quantity'));

        return response()->json($this->carts->present($cart), 201);
    }

    public function updateItem(UpdateCartItemRequest $request, int $itemId): JsonResponse
    {
        $cart = $this->currentCart($request);
        $item = CartItem::where('cart_id', $cart->id)->findOrFail($itemId);

        $this->carts->updateItemQuantity($item, $request->integer('quantity'));

        return response()->json($this->carts->present($cart));
    }

    public function destroyItem(Request $request, int $itemId): JsonResponse
    {
        $cart = $this->currentCart($request);
        $item = CartItem::where('cart_id', $cart->id)->findOrFail($itemId);

        $this->carts->removeItem($item);

        return response()->json($this->carts->present($cart));
    }

    public function destroyUnavailable(Request $request): JsonResponse
    {
        $cart = $this->currentCart($request);
        $removed = $this->carts->removeUnavailable($cart);

        return response()->json([...$this->carts->present($cart), 'removed' => $removed]);
    }

    private function currentCart(Request $request): Cart
    {
        return $request->attributes->get('cart');
    }
}
