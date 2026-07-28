<?php

namespace App\Console\Commands;

use App\Models\Cart;
use App\Models\Setting;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Diario (especificacoes.txt 8.4.25): marca carrinhos sem atividade
 * ha X dias (configuravel) como abandonados.
 */
#[Signature('app:mark-abandoned-carts')]
#[Description('Marca carrinhos ativos sem atividade recente como abandonados')]
class MarkAbandonedCarts extends Command
{
    public function handle(): void
    {
        $days = (int) Setting::get('cart_expiration_days', 30);

        $count = Cart::where('status', 'active')
            ->where('updated_at', '<', now()->subDays($days))
            ->update(['status' => 'abandoned']);

        $this->info("Carrinhos marcados como abandonados: {$count}");
    }
}
