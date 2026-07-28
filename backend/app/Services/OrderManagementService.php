<?php

namespace App\Services;

use App\Models\AdminLog;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Notifications\OrderShippedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderManagementService
{
    /**
     * Muda o status respeitando a maquina de estados (Order::TRANSITIONS,
     * especificacoes.txt secao 3). Cuida dos efeitos colaterais de cada
     * transicao: devolucao de estoque no estorno, liberacao de reserva
     * no cancelamento/expiracao, timestamps.
     */
    public function transition(Order $order, string $newStatus): Order
    {
        if (! $order->canTransitionTo($newStatus)) {
            throw ValidationException::withMessages([
                'status' => "Não é possível mudar de '{$order->status}' para '{$newStatus}'.",
            ]);
        }

        $oldStatus = $order->status;

        DB::transaction(function () use ($order, $newStatus, $oldStatus) {
            match ($newStatus) {
                'estornado' => $this->refundStock($order),
                'cancelado', 'expirado' => $order->reservations()->where('status', 'active')->update(['status' => 'released']),
                default => null,
            };

            $timestamps = match ($newStatus) {
                'pago' => ['paid_at' => now()],
                'enviado' => ['shipped_at' => now()],
                'entregue' => ['delivered_at' => now()],
                'cancelado', 'estornado' => ['cancelled_at' => now()],
                default => [],
            };

            $order->update(['status' => $newStatus, ...$timestamps]);

            AdminLog::record('update_status', 'order', $order->id, ['status' => $oldStatus], ['status' => $newStatus]);
        });

        return $order->fresh();
    }

    /**
     * Devolve o estoque baixado na venda (especificacoes.txt 5.4.21).
     * So se aplica se o pedido ja estava pago (estoque de fato baixado).
     */
    private function refundStock(Order $order): void
    {
        foreach ($order->items as $item) {
            $variant = ProductVariant::whereKey($item->variant_id)->lockForUpdate()->first();

            if (! $variant) {
                continue;
            }

            $newQuantity = $variant->stock_quantity + $item->quantity;
            $variant->update(['stock_quantity' => $newQuantity]);

            StockMovement::create([
                'variant_id' => $variant->id,
                'delta' => $item->quantity,
                'balance_after' => $newQuantity,
                'reason' => 'estorno',
                'order_id' => $order->id,
                'user_id' => auth()->id(),
            ]);
        }
    }

    public function setTrackingCode(Order $order, string $trackingCode): Order
    {
        $order->update(['tracking_code' => $trackingCode]);

        if ($order->status !== 'enviado') {
            $order = $this->transition($order, 'enviado');
        }

        $order->user->notify(new OrderShippedNotification($order));

        return $order->fresh();
    }

    /**
     * Monta uma linha do tempo simples a partir dos timestamps do pedido
     * e do admin_logs (nao existe tabela dedicada de historico no schema).
     */
    public function timeline(Order $order): array
    {
        $events = collect();

        $map = [
            'created_at' => 'Pedido criado',
            'paid_at' => 'Pagamento aprovado',
            'shipped_at' => 'Pedido enviado',
            'delivered_at' => 'Pedido entregue',
            'cancelled_at' => 'Pedido cancelado/estornado',
        ];

        foreach ($map as $column => $label) {
            if ($order->{$column}) {
                $events->push(['label' => $label, 'at' => $order->{$column}]);
            }
        }

        return $events->sortBy('at')->values()->all();
    }
}
