<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PaymentConfirmationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function __construct(private readonly PaymentConfirmationService $confirmation) {}

    /**
     * Webhook da EFI pra pagamentos Pix (especificacoes.txt 4.3).
     *
     * IMPORTANTE (pendente pra quando formos ao ar): a EFI exige mTLS nessa
     * rota - o Nginx precisa validar o certificado cliente que a EFI envia
     * antes de deixar a requisicao chegar aqui. Isso e config de servidor,
     * nao da app; ver especificacoes.txt 4.3.13 quando configurarmos o dominio.
     */
    public function efiPix(Request $request): JsonResponse
    {
        $payload = $request->all();

        Log::info('Webhook EFI recebido', $payload);

        foreach ($payload['pix'] ?? [] as $event) {
            if ($txid = $event['txid'] ?? null) {
                $this->confirmation->confirmByTxid($txid, $payload);
            }
        }

        // Responde 200 sempre e rapido (especificacoes.txt 4.3.16) - processamento
        // pesado nao existe aqui ainda, mas se crescer, isso vira um Job em fila.
        return response()->json(['received' => true]);
    }
}
