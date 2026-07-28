<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Notifications\PaymentApprovedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentConfirmationService
{
    /**
     * Confirma um pagamento Pix pelo txid. Idempotente: se ja estava
     * aprovado, nao faz nada de novo (especificacoes.txt 4.3.15).
     * Usado tanto pelo webhook quanto pelo job de redundancia (4.3.18).
     */
    public function confirmByTxid(string $txid, array $rawResponse = []): bool
    {
        $payment = Payment::where('efi_txid', $txid)->first();

        if (! $payment) {
            Log::warning("Webhook EFI: txid nao encontrado: {$txid}");

            return false;
        }

        if ($payment->status === 'aprovado') {
            return true; // ja processado, idempotente
        }

        DB::transaction(function () use ($payment, $rawResponse) {
            $payment->update([
                'status' => 'aprovado',
                'paid_at' => now(),
                'raw_response' => $rawResponse ?: $payment->raw_response,
            ]);

            $order = $payment->order;
            $order->update(['status' => 'pago', 'paid_at' => now()]);

            foreach ($order->items as $item) {
                $variant = ProductVariant::whereKey($item->variant_id)->lockForUpdate()->first();

                if ($variant) {
                    $newQuantity = max(0, $variant->stock_quantity - $item->quantity);
                    $variant->update(['stock_quantity' => $newQuantity]);

                    StockMovement::create([
                        'variant_id' => $variant->id,
                        'delta' => -$item->quantity,
                        'balance_after' => $newQuantity,
                        'reason' => 'venda',
                        'order_id' => $order->id,
                    ]);
                }
            }

            $order->reservations()->where('status', 'active')->update(['status' => 'consumed']);

            $order->user->notify(new PaymentApprovedNotification($order));
        });

        return true;
    }
}
