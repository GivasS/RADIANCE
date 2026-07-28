<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentApprovedNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly Order $order) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Pagamento aprovado - Pedido {$this->order->order_number}")
            ->greeting("Recebemos seu pagamento, {$notifiable->name}!")
            ->line("O pedido {$this->order->order_number} foi confirmado e já está sendo preparado.")
            ->line('Valor total: R$ '.number_format((float) $this->order->total, 2, ',', '.'))
            ->line('Você receberá um novo e-mail assim que ele for enviado, com o código de rastreio.');
    }
}
