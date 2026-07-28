<?php

namespace App\Console\Commands;

use App\Models\Payment;
use App\Services\PaymentConfirmationService;
use App\Services\Payments\PaymentGatewayInterface;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Roda a cada 5 minutos (especificacoes.txt 4.3.18): redundancia do
 * webhook - consulta direto na EFI o status de pagamentos pendentes
 * ha mais de 3 minutos, caso o webhook nao tenha chegado.
 */
#[Signature('app:check-pending-payments')]
#[Description('Consulta na EFI o status de pagamentos pendentes (redundancia do webhook)')]
class CheckPendingPayments extends Command
{
    public function handle(PaymentGatewayInterface $gateway, PaymentConfirmationService $confirmation): void
    {
        $payments = Payment::where('status', 'pendente')
            ->where('created_at', '<', now()->subMinutes(3))
            ->whereNotNull('efi_txid')
            ->get();

        $confirmed = 0;

        foreach ($payments as $payment) {
            try {
                $status = $gateway->checkStatus($payment->efi_txid);

                if ($status === 'CONCLUIDA') {
                    $confirmation->confirmByTxid($payment->efi_txid);
                    $confirmed++;
                }
            } catch (\Throwable $e) {
                $this->warn("Falha ao consultar txid {$payment->efi_txid}: {$e->getMessage()}");
            }
        }

        $this->info("Pagamentos pendentes checados: {$payments->count()} | Confirmados agora: {$confirmed}");
    }
}
