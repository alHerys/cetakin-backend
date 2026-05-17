<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AtkOrderStatusHistory extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = ['order_id', 'status'];

    public function order(): BelongsTo
    {
        return $this->belongsTo(AtkOrder::class, 'order_id');
    }
}
