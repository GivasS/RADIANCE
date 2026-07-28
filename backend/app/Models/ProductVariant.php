<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'product_id', 'variant_name', 'variant_value', 'sku_suffix',
    'price_override', 'stock_quantity', 'position', 'active',
])]
#[Hidden(['del_flag'])]
class ProductVariant extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'price_override' => 'decimal:2',
            'active' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class, 'variant_id');
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(StockReservation::class, 'variant_id');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'variant_id');
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'variant_id');
    }

    public function activeReservationsQuantity(): int
    {
        return $this->reservations()
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->sum('quantity');
    }

    public function availableStock(): int
    {
        return $this->stock_quantity - $this->activeReservationsQuantity();
    }
}
