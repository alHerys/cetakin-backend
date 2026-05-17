<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AtkOrderItem extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = ['atk_order_id', 'atk_product_id', 'name', 'quantity', 'unit_price', 'subtotal'];

    public function order(): BelongsTo
    {
        return $this->belongsTo(AtkOrder::class, 'atk_order_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(AtkProduct::class, 'atk_product_id');
    }
}
