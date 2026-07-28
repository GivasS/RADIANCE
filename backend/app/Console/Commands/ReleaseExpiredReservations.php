<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\StockReservation;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Roda a cada 2 minutos (especificacoes.txt 1.3.18 / 8.4.23):
 * libera reservas de estoque expiradas e cancela pedidos vencidos.
 */
#[Signature('app:release-expired-reservations')]
#[Description('Libera reservas de estoque expiradas e cancela pedidos vencidos')]
class ReleaseExpiredReservations extends Command
{
    public function handle(): void
    {
        $releasedReservations = StockReservation::where('status', 'active')
            ->where('expires_at', '<', now())
            ->update(['status' => 'expired']);

        $cancelledOrders = Order::where('status', 'aguardando_pagamento')
            ->where('created_at', '<', now()->subMinutes(20))
            ->update(['status' => 'expirado', 'cancelled_at' => now()]);

        $this->info("Reservas liberadas: {$releasedReservations} | Pedidos cancelados: {$cancelledOrders}");
    }
}
