<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'category_id', 'name', 'slug', 'sku', 'short_description', 'description',
    'price', 'promo_price', 'weight_grams', 'active', 'featured',
])]
#[Hidden(['del_flag'])]
class Product extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'promo_price' => 'decimal:2',
            'active' => 'boolean',
            'featured' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderByDesc('is_main')->orderBy('position');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Regra de negocio (especificacoes.txt 1.2.9): so aparece pro cliente se
     * active=1, nao deletado, e tiver ao menos uma variacao com estoque
     * disponivel (descontando reservas ativas e nao expiradas).
     */
    public function scopePubliclyVisible(Builder $query): Builder
    {
        return $query->where('active', true)->whereHas('variants', function (Builder $q) {
            $q->where('active', true)->whereRaw(
                'stock_quantity - COALESCE((
                    SELECT SUM(quantity) FROM stock_reservations
                    WHERE stock_reservations.variant_id = product_variants.id
                      AND stock_reservations.status = "active"
                      AND stock_reservations.expires_at > NOW()
                      AND stock_reservations.deleted_at IS NULL
                ), 0) > 0'
            );
        });
    }
}
