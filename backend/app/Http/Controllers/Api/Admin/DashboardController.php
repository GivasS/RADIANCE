<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * especificacoes.txt 5.2: cards, alerta de estoque baixo, ultimos
     * pedidos e grafico de vendas dos ultimos 30 dias.
     */
    public function index(): JsonResponse
    {
        $paidStatuses = ['pago', 'separando', 'enviado', 'entregue'];

        $salesToday = Order::whereIn('status', $paidStatuses)->whereDate('paid_at', today())->sum('total');
        $salesMonth = Order::whereIn('status', $paidStatuses)->whereMonth('paid_at', now()->month)->whereYear('paid_at', now()->year)->sum('total');
        $pendingOrders = Order::whereIn('status', ['pendente', 'aguardando_pagamento'])->count();
        $averageTicket = Order::whereIn('status', $paidStatuses)->whereMonth('paid_at', now()->month)->avg('total');

        $lowStockThreshold = (int) Setting::get('low_stock_alert', 5);
        $lowStock = ProductVariant::with('product:id,name')
            ->where('active', true)
            ->where('stock_quantity', '<', $lowStockThreshold)
            ->orderBy('stock_quantity')
            ->limit(20)
            ->get(['id', 'product_id', 'variant_value', 'stock_quantity']);

        $lastOrders = Order::with('user:id,name')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get(['id', 'order_number', 'user_id', 'status', 'total', 'created_at']);

        $salesChart = Order::whereIn('status', $paidStatuses)
            ->where('paid_at', '>=', now()->subDays(30))
            ->select(DB::raw('DATE(paid_at) as date'), DB::raw('SUM(total) as total'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return response()->json([
            'sales_today' => round((float) $salesToday, 2),
            'sales_month' => round((float) $salesMonth, 2),
            'pending_orders' => $pendingOrders,
            'average_ticket' => round((float) $averageTicket, 2),
            'low_stock' => $lowStock,
            'last_orders' => $lastOrders,
            'sales_chart' => $salesChart,
        ]);
    }
}
