<?php

namespace App\Services\Payments;

use App\Models\Order;
use Illuminate\Support\Str;

/**
 * Gateway falso, usado enquanto as chaves reais da EFI nao chegam.
 * Gera uma cobranca "Pix" instantanea, sem chamar nenhuma API externa,
 * so pra dar pra testar o fluxo de checkout de ponta a ponta.
 *
 * NUNCA usar em producao - trocado automaticamente pelo EfiPixGateway
 * assim que EFI_CLIENT_ID for preenchido no .env (ver AppServiceProvider).
 */
class FakePixGateway implements PaymentGatewayInterface
{
    public function createPixCharge(Order $order): array
    {
        $txid = Str::random(32);

        return [
            'txid' => $txid,
            'qr_code' => "00020126FAKE-PIX-{$txid}-VALOR{$order->total}5204000053039865802BR",
            'qr_code_image' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=',
            'copia_e_cola' => "00020126FAKE-PIX-{$txid}",
            'expires_at' => now()->addSeconds(1200),
            'raw_response' => ['fake' => true, 'order_id' => $order->id],
        ];
    }

    public function checkStatus(string $txid): string
    {
        // No fake gateway nunca confirma sozinho - so via /simulate-payment (ver rotas de dev).
        return 'ATIVA';
    }
}
