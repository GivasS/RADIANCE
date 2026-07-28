<?php

namespace App\Services\Payments;

use App\Models\Order;

interface PaymentGatewayInterface
{
    /**
     * Cria uma cobranca Pix pro pedido e retorna os dados pra tela de pagamento.
     *
     * @return array{txid: string, qr_code: string, qr_code_image: string, copia_e_cola: string, expires_at: \Illuminate\Support\Carbon, raw_response: array}
     */
    public function createPixCharge(Order $order): array;

    /**
     * Consulta o status atual de uma cobranca na EFI (redundancia do webhook,
     * especificacoes.txt 4.3.18). Retorna 'ATIVA', 'CONCLUIDA', 'REMOVIDA_PELO_USUARIO_RECEBEDOR' etc.
     */
    public function checkStatus(string $txid): string;
}
