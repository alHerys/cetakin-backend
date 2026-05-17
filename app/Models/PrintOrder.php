<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PrintOrder extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id', 'shop_id', 'file_url', 'paper_size', 'color_mode',
        'sides', 'binding', 'copies', 'total_pages', 'final_price', 'notes', 'status',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(PrintOrderStatusHistory::class, 'order_id');
    }

    public function review(): HasOne
    {
        return $this->hasOne(Review::class, 'print_order_id');
    }
}
