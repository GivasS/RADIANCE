<?php

namespace App\Notifications;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderCreatedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Order $order,
        public readonly Payment $payment,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Pedido {$this->order->order_number} - instruções de pagamento")
            ->greeting("Olá, {$notifiable->name}!")
            ->line("Recebemos seu pedido {$this->order->order_number}, no valor de R$ ".number_format((float) $this->order->total, 2, ',', '.'))
            ->line('Para confirmar, pague via Pix usando o código copia-e-cola abaixo em até 20 minutos:')
            ->line($this->payment->copia_e_cola)
            ->line('Depois de pago, o pagamento é confirmado automaticamente.');
    }
}
