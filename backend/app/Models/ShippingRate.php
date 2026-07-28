<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'name', 'state', 'zipcode_start', 'zipcode_end', 'price',
    'delivery_days', 'free_above', 'position', 'active',
])]
class ShippingRate extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'free_above' => 'decimal:2',
            'active' => 'boolean',
        ];
    }
}
