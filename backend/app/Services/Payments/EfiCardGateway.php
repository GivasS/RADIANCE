<?php

namespace App\Services\Payments;

use App\Models\Order;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Integracao com a API de Cobrancas da EFI (cartao de credito).
 * Diferente da API Pix: usa OAuth2 com Basic Auth simples, sem certificado
 * mTLS (dev.efipay.com.br/docs/api-cobrancas/credenciais).
 */
class EfiCardGateway implements CardGatewayInterface
{
    public function chargeCard(Order $order, array $cardCharge): array
    {
        $token = $this->getAccessToken();

        $response = Http::withToken($token)
            ->baseUrl($this->baseUrl())
            ->post('/v1/charge/one-step', [
                'items' => [[
                    'name' => "Pedido {$order->order_number} - Radiance",
                    'value' => (int) round($order->total * 100),
                    'amount' => 1,
                ]],
                'payment' => [
                    'credit_card' => [
                        'customer' => $cardCharge['customer'],
                        'installments' => $cardCharge['installments'],
                        'payment_token' => $cardCharge['payment_token'],
                        'billing_address' => $cardCharge['billing_address'],
                    ],
                ],
            ])
            ->throw();

        $body = $response->json();
        $data = $body['data'] ?? [];

        return [
            'status' => $data['status'] ?? 'unpaid',
            'charge_id' => $data['charge_id'] ?? null,
            'installments' => $data['installments'] ?? $cardCharge['installments'],
            'installment_value' => isset($data['installment_value']) ? $data['installment_value'] / 100 : null,
            'refusal_reason' => $data['refusal']['reason'] ?? null,
            'raw_response' => $body,
        ];
    }

    private function getAccessToken(): string
    {
        return Cache::remember('efi:cobrancas_access_token', 3000, function () {
            $clientId = config('services.efi.client_id');
            $clientSecret = config('services.efi.client_secret');

            if (! $clientId || ! $clientSecret) {
                throw new RuntimeException('EFI_CLIENT_ID / EFI_CLIENT_SECRET nao configurados.');
            }

            $response = Http::withBasicAuth($clientId, $clientSecret)
                ->post($this->baseUrl().'/v1/authorize', ['grant_type' => 'client_credentials'])
                ->throw()
                ->json();

            return $response['access_token'];
        });
    }

    private function baseUrl(): string
    {
        return config('services.efi.sandbox')
            ? 'https://cobrancas-h.api.efipay.com.br'
            : 'https://cobrancas.api.efipay.com.br';
    }
}
