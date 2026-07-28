<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\CouponUse;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class CouponService
{
    /**
     * Valida o cupom e retorna o desconto calculado sobre o subtotal
     * (especificacoes.txt 1.6.29-33). Lanca ValidationException se invalido.
     */
    public function validate(string $code, float $subtotal, ?User $user): array
    {
        $coupon = Coupon::where('code', $code)->first();

        if (! $coupon || ! $coupon->isValidNow()) {
            throw ValidationException::withMessages(['coupon' => 'Cupom inválido ou expirado.']);
        }

        if ($subtotal < (float) $coupon->min_order_value) {
            throw ValidationException::withMessages([
                'coupon' => "Esse cupom exige compra mínima de R$ ".number_format((float) $coupon->min_order_value, 2, ',', '.'),
            ]);
        }

        if ($user) {
            $usedByUser = CouponUse::where('coupon_id', $coupon->id)->where('user_id', $user->id)->count();

            if ($usedByUser >= $coupon->max_uses_per_user) {
                throw ValidationException::withMessages(['coupon' => 'Você já utilizou esse cupom.']);
            }
        }

        $discount = $coupon->type === 'percent'
            ? round($subtotal * ((float) $coupon->value / 100), 2)
            : min((float) $coupon->value, $subtotal);

        return ['coupon' => $coupon, 'discount' => $discount];
    }
}
