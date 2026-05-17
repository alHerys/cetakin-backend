<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Shop extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id', 'shop_name', 'shop_address', 'shop_phone', 'shop_description',
        'shop_photo_url', 'open_time', 'close_time', 'operating_days',
        'latitude', 'longitude', 'status', 'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'operating_days' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function service(): HasOne
    {
        return $this->hasOne(ShopService::class);
    }

    public function pricing(): HasOne
    {
        return $this->hasOne(ShopPricing::class);
    }

    public function atkProducts(): HasMany
    {
        return $this->hasMany(AtkProduct::class);
    }

    public function printOrders(): HasMany
    {
        return $this->hasMany(PrintOrder::class);
    }

    public function atkOrders(): HasMany
    {
        return $this->hasMany(AtkOrder::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }
}
