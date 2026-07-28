<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'order_number', 'user_id', 'status', 'subtotal', 'discount_total', 'shipping_total', 'total',
    'coupon_id', 'coupon_code', 'shipping_method', 'shipping_days', 'tracking_code',
    'customer_snapshot', 'shipping_snapshot', 'notes',
    'paid_at', 'shipped_at', 'delivered_at', 'cancelled_at',
])]
#[Hidden(['del_flag'])]
class Order extends Model
{
    use SoftDeletes;

    /** Transições de status permitidas — ver especificacoes.txt secao 3 */
    public const TRANSITIONS = [
        'pendente' => ['aguardando_pagamento', 'cancelado'],
        'aguardando_pagamento' => ['pago', 'expirado', 'cancelado'],
        'pago' => ['separando', 'estornado'],
        'separando' => ['enviado', 'estornado'],
        'enviado' => ['entregue'],
        'entregue' => [],
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'discount_total' => 'decimal:2',
            'shipping_total' => 'decimal:2',
            'total' => 'decimal:2',
            'customer_snapshot' => 'array',
            'shipping_snapshot' => 'array',
            'paid_at' => 'datetime',
            'shipped_at' => 'datetime',
            'delivered_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(StockReservation::class);
    }

    public function canTransitionTo(string $newStatus): bool
    {
        return in_array($newStatus, self::TRANSITIONS[$this->status] ?? [], true);
    }
}
