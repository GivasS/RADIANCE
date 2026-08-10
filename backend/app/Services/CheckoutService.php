<?php

namespace App\Services;

use App\Models\Address;
use App\Models\Cart;
use App\Models\CouponUse;
use App\Models\Order;
use App\Models\Payment;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Models\StockReservation;
use App\Models\User;
use App\Notifications\OrderCreatedNotification;
use App\Services\Payments\CardGatewayInterface;
use App\Services\Payments\PaymentGatewayInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CheckoutService
{
    public function __construct(
        private readonly CartService $carts,
        private readonly ShippingService $shipping,
        private readonly CouponService $coupons,
        private readonly PaymentGatewayInterface $gateway,
        private readonly CardGatewayInterface $cardGateway,
    ) {}

    /**
     * Fluxo completo do checkout (especificacoes.txt secao 4.2, passos 1-8):
     * revalida estoque, cria pedido pendente + itens (snapshot) + reservas
     * com TTL, gera a cobranca (Pix ou cartao) e esvazia o carrinho.
     *
     * @param  array{payment_token: string, installments: int, holder_document: string, holder_birth: string}|null  $card
     */
    public function checkout(User $user, Cart $cart, int $addressId, int $shippingRateId, ?string $couponCode, string $paymentMethod, ?array $card = null): array
    {
        $presented = $this->carts->present($cart);

        if (empty($presented['items'])) {
            throw ValidationException::withMessages(['cart' => 'Carrinho vazio.']);
        }

        if ($presented['has_unavailable']) {
            throw ValidationException::withMessages(['cart' => 'Remova os itens indisponíveis antes de continuar.']);
        }

        $address = Address::where('user_id', $user->id)->findOrFail($addressId);
        $rate = $this->shipping->findRate($shippingRateId);

        if (! $rate) {
            throw ValidationException::withMessages(['shipping_rate_id' => 'Frete inválido.']);
        }

        $subtotal = $presented['subtotal'];
        $discount = 0.0;
        $coupon = null;

        if ($couponCode) {
            $result = $this->coupons->validate($couponCode, $subtotal, $user);
            $coupon = $result['coupon'];
            $discount = $result['discount'];
        }

        $shippingOptions = $this->shipping->quote($address->zipcode, $subtotal);
        $chosenOption = $shippingOptions->firstWhere('id', $rate->id);
        $shippingTotal = $chosenOption['price'] ?? (float) $rate->price;

        $total = round($subtotal - $discount + $shippingTotal, 2);

        return DB::transaction(function () use (
            $user, $cart, $presented, $address, $rate, $coupon, $discount, $subtotal, $shippingTotal, $total, $paymentMethod, $card
        ) {
            $order = Order::create([
                'order_number' => $this->generateOrderNumber(),
                'user_id' => $user->id,
                'status' => 'pendente',
                'subtotal' => $subtotal,
                'discount_total' => $discount,
                'shipping_total' => $shippingTotal,
                'total' => $total,
                'coupon_id' => $coupon?->id,
                'coupon_code' => $coupon?->code,
                'shipping_method' => $rate->name,
                'shipping_days' => $rate->delivery_days,
                'customer_snapshot' => ['name' => $user->name, 'email' => $user->email, 'cpf' => $user->cpf, 'phone' => $user->phone],
                'shipping_snapshot' => $address->only([
                    'recipient_name', 'zipcode', 'street', 'number', 'complement', 'district', 'city', 'state',
                ]),
            ]);

            foreach ($presented['items'] as $item) {
                // SELECT ... FOR UPDATE antes de reservar (especificacoes.txt 8.3.20).
                $variant = ProductVariant::whereKey($item['variant_id'])->lockForUpdate()->firstOrFail();
                $available = $this->carts->availableStock($variant);

                if ($available < $item['quantity']) {
                    throw ValidationException::withMessages([
                        'cart' => "Estoque insuficiente para {$item['product']->name}. Volte ao carrinho.",
                    ]);
                }

                $order->items()->create([
                    'product_id' => $item['product_id'],
                    'variant_id' => $item['variant_id'],
                    'product_name' => $item['product']->name,
                    'variant_label' => $item['variant']->variant_value,
                    'sku' => $item['product']->sku,
                    'image_path' => $item['product']->images->first()?->path,
                    'unit_price' => $item['effective_unit_price'],
                    'quantity' => $item['quantity'],
                    'line_total' => round($item['effective_unit_price'] * $item['quantity'], 2),
                ]);

                StockReservation::create([
                    'variant_id' => $variant->id,
                    'order_id' => $order->id,
                    'quantity' => $item['quantity'],
                    'expires_at' => now()->addMinutes((int) Setting::get('reservation_ttl_minutes', 20)),
                    'status' => 'active',
                ]);
            }

            if ($coupon) {
                CouponUse::create([
                    'coupon_id' => $coupon->id,
                    'user_id' => $user->id,
                    'order_id' => $order->id,
                    'discount_applied' => $discount,
                ]);
                $coupon->increment('used_count');
            }

            $order->update(['status' => 'aguardando_pagamento']);

            $payment = $paymentMethod === 'cartao'
                ? $this->createCardPayment($order, $user, $address, $card)
                : $this->createPixPayment($order);

            // Esvazia o carrinho - os itens ja viraram order_items (especificacoes.txt 1.1.7).
            // Excecao: cartao recusado deixa o carrinho intacto pra dar pra
            // tentar de novo sem ter que recolocar tudo (nao teve pagamento
            // nenhum, diferente do Pix que so fica "pendente" aguardando).
            if (! ($paymentMethod === 'cartao' && $payment->status === 'recusado')) {
                $cart->items()->delete();
                $cart->update(['status' => 'converted']);
                $cart->delete();
            }

            $user->notify(new OrderCreatedNotification($order, $payment));

            return ['order' => $order->fresh('items'), 'payment' => $payment];
        });
    }

    private function createPixPayment(Order $order): Payment
    {
        $pix = $this->gateway->createPixCharge($order);

        return Payment::create([
            'order_id' => $order->id,
            'provider' => 'efi',
            'method' => 'pix',
            'efi_txid' => $pix['txid'],
            'qr_code' => $pix['qr_code'],
            'qr_code_image' => $pix['qr_code_image'],
            'copia_e_cola' => $pix['copia_e_cola'],
            'status' => 'pendente',
            'amount' => $order->total,
            'raw_response' => $pix['raw_response'],
            'expires_at' => $pix['expires_at'],
        ]);
    }

    /**
     * @param  array{payment_token: string, installments: int, holder_document: string, holder_birth: string}  $card
     */
    private function createCardPayment(Order $order, User $user, Address $address, array $card): Payment
    {
        $charge = $this->cardGateway->chargeCard($order, [
            'payment_token' => $card['payment_token'],
            'installments' => $card['installments'],
            'customer' => [
                'name' => $user->name,
                'cpf' => preg_replace('/\D/', '', $card['holder_document']),
                'email' => $user->email,
                'phone_number' => preg_replace('/\D/', '', $user->phone ?? ''),
                'birth' => $card['holder_birth'],
            ],
            'billing_address' => [
                'street' => $address->street,
                'number' => $address->number,
                'neighborhood' => $address->district,
                'zipcode' => preg_replace('/\D/', '', $address->zipcode),
                'city' => $address->city,
                'state' => $address->state,
            ],
        ]);

        // Cartao e sincrono - ja sabemos aprovado/recusado na hora, sem
        // depender de webhook como o Pix.
        $status = match ($charge['status']) {
            'approved' => 'aprovado',
            'unpaid' => 'recusado',
            default => 'pendente',
        };

        if ($status === 'aprovado') {
            $order->update(['status' => 'pago']);
        }

        return Payment::create([
            'order_id' => $order->id,
            'provider' => 'efi',
            'method' => 'credit_card',
            'efi_charge_id' => $charge['charge_id'],
            'status' => $status,
            'amount' => $order->total,
            'installments' => $charge['installments'],
            'raw_response' => $charge['raw_response'],
            'paid_at' => $status === 'aprovado' ? now() : null,
        ]);
    }

    private function generateOrderNumber(): string
    {
        do {
            $number = 'RAD'.now()->format('ymd').strtoupper(Str::random(6));
        } while (Order::where('order_number', $number)->exists());

        return $number;
    }
}
