<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\ShippingRate;
use Illuminate\Support\Collection;

class ShippingService
{
    /**
     * Lista as opcoes de frete disponiveis pro CEP, ja aplicando frete gratis
     * quando o subtotal atinge o limite (especificacoes.txt 1.6.29).
     */
    public function quote(string $zipcode, float $subtotal): Collection
    {
        $digits = preg_replace('/\D/', '', $zipcode);
        $defaultThreshold = (float) Setting::get('free_shipping_threshold', 219.00);

        return ShippingRate::query()
            ->where('active', true)
            ->where(function ($q) use ($digits) {
                $q->whereNull('zipcode_start')
                    ->orWhere(fn ($q2) => $q2->where('zipcode_start', '<=', $digits)->where('zipcode_end', '>=', $digits));
            })
            ->orderBy('position')
            ->get()
            ->map(function (ShippingRate $rate) use ($subtotal, $defaultThreshold) {
                $threshold = $rate->free_above ?? $defaultThreshold;
                $isFree = $threshold !== null && $subtotal >= $threshold;

                return [
                    'id' => $rate->id,
                    'name' => $rate->name,
                    'price' => $isFree ? 0.0 : (float) $rate->price,
                    'delivery_days' => $rate->delivery_days,
                    'free' => $isFree,
                ];
            });
    }

    public function findRate(int $rateId): ?ShippingRate
    {
        return ShippingRate::where('active', true)->find($rateId);
    }
}
