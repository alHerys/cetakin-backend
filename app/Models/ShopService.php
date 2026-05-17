<?php

namespace App\Models;

use App\Casts\PgArray;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShopService extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = ['shop_id', 'paper_sizes', 'color_modes', 'sides', 'bindings'];

    protected function casts(): array
    {
        return [
            'paper_sizes' => PgArray::class,
            'color_modes' => PgArray::class,
            'sides'       => PgArray::class,
            'bindings'    => PgArray::class,
        ];
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }
}
