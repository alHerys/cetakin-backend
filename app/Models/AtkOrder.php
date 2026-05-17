<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AtkOrder extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['user_id', 'shop_id', 'final_price', 'notes', 'status'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(AtkOrderItem::class, 'atk_order_id');
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(AtkOrderStatusHistory::class, 'order_id');
    }

    public function review(): HasOne
    {
        return $this->hasOne(Review::class, 'atk_order_id');
    }
}
