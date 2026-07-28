<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    /**
     * "Meus Pedidos" - so os pedidos do proprio usuario logado.
     */
    public function index(Request $request): JsonResponse
    {
        $orders = Order::where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->paginate(10);

        return response()->json($orders);
    }

    /**
     * Detalhe de um pedido do proprio usuario (usado na tela pos-checkout
     * pra mostrar o QR Code do Pix, e em "Meus Pedidos").
     */
    public function show(Request $request, string $orderNumber): JsonResponse
    {
        $order = Order::where('user_id', $request->user()->id)
            ->where('order_number', $orderNumber)
            ->with(['items', 'payment'])
            ->firstOrFail();

        return response()->json(['order' => $order]);
    }
}
