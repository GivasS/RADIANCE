<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\OrderStatusRequest;
use App\Http\Requests\Admin\TrackingCodeRequest;
use App\Models\Order;
use App\Services\OrderManagementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrderController extends Controller
{
    public function __construct(private readonly OrderManagementService $orders) {}

    /**
     * Listagem com filtro por status, periodo e busca por numero/cliente
     * (especificacoes.txt 5.4.17).
     */
    public function index(Request $request): JsonResponse
    {
        $orders = $this->filtered($request)
            ->with('user:id,name,email')
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 20));

        return response()->json($orders);
    }

    public function show(Order $order): JsonResponse
    {
        $order->load(['user', 'items', 'payment', 'coupon']);

        return response()->json([
            'order' => $order,
            'timeline' => $this->orders->timeline($order),
        ]);
    }

    public function updateStatus(OrderStatusRequest $request, Order $order): JsonResponse
    {
        $order = $this->orders->transition($order, $request->string('status'));

        return response()->json(['order' => $order]);
    }

    public function setTracking(TrackingCodeRequest $request, Order $order): JsonResponse
    {
        $order = $this->orders->setTrackingCode($order, $request->string('tracking_code'));

        return response()->json(['order' => $order]);
    }

    /**
     * Cancelamento (especificacoes.txt 5.4.21): se ja tinha baixado estoque
     * (pago/separando), estorna e devolve; senao, so cancela.
     */
    public function cancel(Order $order): JsonResponse
    {
        $target = in_array($order->status, ['pago', 'separando'], true) ? 'estornado' : 'cancelado';
        $order = $this->orders->transition($order, $target);

        return response()->json(['order' => $order]);
    }

    /**
     * Exportacao CSV (especificacoes.txt 5.4.22).
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $orders = $this->filtered($request)->with('user:id,name,email')->orderByDesc('created_at')->get();

        return response()->streamDownload(function () use ($orders) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Número', 'Cliente', 'E-mail', 'Status', 'Total', 'Data']);

            foreach ($orders as $order) {
                fputcsv($out, [
                    $order->order_number,
                    $order->user->name,
                    $order->user->email,
                    $order->status,
                    number_format((float) $order->total, 2, ',', '.'),
                    $order->created_at->format('d/m/Y H:i'),
                ]);
            }

            fclose($out);
        }, 'pedidos.csv');
    }

    private function filtered(Request $request)
    {
        return Order::query()
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('from'), fn ($q) => $q->where('created_at', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($q) => $q->where('created_at', '<=', $request->date('to')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = $request->string('search');
                $q->where(fn ($q2) => $q2->where('order_number', 'like', "%{$term}%")
                    ->orWhereHas('user', fn ($q3) => $q3->where('name', 'like', "%{$term}%")));
            });
    }
}
