<?php

namespace App\Services\Payments;

use App\Models\Order;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Integracao real com a EFI (Pix), seguindo especificacoes.txt secao 4.
 *
 * ATENCAO: escrito a partir da documentacao publica da EFI, sem chaves de
 * homologacao pra testar de verdade ainda. Antes de ir pra producao, validar
 * com uma cobranca real em sandbox - principalmente o formato do certificado
 * (.p12) esperado pelo Guzzle/cURL, que costuma exigir ajuste caso a EFI
 * forneca o arquivo num formato diferente do esperado aqui.
 */
class EfiPixGateway implements PaymentGatewayInterface
{
    public function createPixCharge(Order $order): array
    {
        $token = $this->getAccessToken();
        $txid = Str::random(32);

        $cobranca = $this->client($token)->put("/v2/cob/{$txid}", [
            'calendario' => ['expiracao' => 1200],
            'valor' => ['original' => number_format((float) $order->total, 2, '.', '')],
            'chave' => config('services.efi.pix_key'),
            'solicitacaoPagador' => "Pedido {$order->order_number} - Radiance",
        ])->throw()->json();

        $locId = $cobranca['loc']['id'];

        $qrcode = $this->client($token)->get("/v2/loc/{$locId}/qrcode")->throw()->json();

        return [
            'txid' => $txid,
            'qr_code' => $qrcode['qrcode'] ?? '',
            'qr_code_image' => $qrcode['imagemQrcode'] ?? '',
            'copia_e_cola' => $qrcode['qrcode'] ?? '',
            'expires_at' => now()->addSeconds(1200),
            'raw_response' => $cobranca,
        ];
    }

    public function checkStatus(string $txid): string
    {
        $token = $this->getAccessToken();
        $cobranca = $this->client($token)->get("/v2/cob/{$txid}")->throw()->json();

        return $cobranca['status'] ?? 'ATIVA';
    }

    private function getAccessToken(): string
    {
        return Cache::remember('efi:access_token', 3000, function () {
            $clientId = config('services.efi.client_id');
            $clientSecret = config('services.efi.client_secret');

            if (! $clientId || ! $clientSecret) {
                throw new RuntimeException('EFI_CLIENT_ID / EFI_CLIENT_SECRET nao configurados.');
            }

            $response = Http::withOptions(['cert' => config('services.efi.certificate_path')])
                ->withBasicAuth($clientId, $clientSecret)
                ->post($this->baseUrl().'/oauth/token', ['grant_type' => 'client_credentials'])
                ->throw()
                ->json();

            return $response['access_token'];
        });
    }

    private function client(string $token)
    {
        return Http::withOptions(['cert' => config('services.efi.certificate_path')])
            ->withToken($token)
            ->baseUrl($this->baseUrl());
    }

    private function baseUrl(): string
    {
        return config('services.efi.sandbox')
            ? 'https://pix-h.api.efipay.com.br'
            : 'https://pix.api.efipay.com.br';
    }
}
