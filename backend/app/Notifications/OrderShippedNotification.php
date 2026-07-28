<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderShippedNotification extends Notification
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
            ->subject("Pedido {$this->order->order_number} enviado")
            ->greeting("Boas notícias, {$notifiable->name}!")
            ->line("Seu pedido {$this->order->order_number} foi enviado.")
            ->line("Código de rastreio: {$this->order->tracking_code}");
    }
}
