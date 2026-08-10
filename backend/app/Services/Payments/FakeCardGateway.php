<?php

namespace App\Services\Payments;

use App\Models\Order;

/**
 * Gateway falso, usado enquanto as chaves reais da EFI nao chegam.
 * NUNCA usar em producao - trocado automaticamente pelo EfiCardGateway
 * assim que EFI_CLIENT_ID for preenchido no .env (ver AppServiceProvider).
 */
class FakeCardGateway implements CardGatewayInterface
{
    public function chargeCard(Order $order, array $cardCharge): array
    {
        // "4000000000000002" e variantes costumam ser usadas como cartao de
        // teste que recusa - deixa dar pra testar os dois fluxos localmente.
        $approved = ! str_ends_with($cardCharge['payment_token'] ?? '', '-recusar');

        return [
            'status' => $approved ? 'approved' : 'unpaid',
            'charge_id' => $approved ? random_int(100000, 999999) : null,
            'installments' => $cardCharge['installments'],
            'installment_value' => round($order->total / $cardCharge['installments'], 2),
            'refusal_reason' => $approved ? null : 'Cartao de teste recusado (fake gateway).',
            'raw_response' => ['fake' => true, 'order_id' => $order->id],
        ];
    }
}
