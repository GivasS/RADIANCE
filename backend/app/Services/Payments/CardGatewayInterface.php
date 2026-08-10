<?php

namespace App\Services\Payments;

use App\Models\Order;

interface CardGatewayInterface
{
    /**
     * Cobra o cartao via payment_token (tokenizado no navegador - o backend
     * nunca ve numero/cvv crus). Sincrono: a EFI responde aprovado/recusado
     * na hora, sem precisar de webhook como o Pix.
     *
     * @param  array{payment_token: string, installments: int, customer: array, billing_address: array}  $cardCharge
     * @return array{status: string, charge_id: ?int, installments: int, installment_value: ?float, refusal_reason: ?string, raw_response: array}
     */
    public function chargeCard(Order $order, array $cardCharge): array;
}
