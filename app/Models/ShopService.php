<?php

namespace App\Models;

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
            'paper_sizes' => 'array',
            'color_modes' => 'array',
            'sides'       => 'array',
            'bindings'    => 'array',
        ];
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }
}
