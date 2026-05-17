<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShopPricing extends Model
{
    use HasUuids;

    protected $table = 'shop_pricing';

    public $timestamps = false;

    protected $fillable = [
        'shop_id', 'black_and_white_per_page', 'full_color_per_page',
        'double_side_surcharge', 'binding_prices',
    ];

    protected function casts(): array
    {
        return [
            'binding_prices' => 'array',
        ];
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }
}
