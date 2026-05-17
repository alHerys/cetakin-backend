<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrintOrderStatusHistory extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = ['order_id', 'status'];

    public function order(): BelongsTo
    {
        return $this->belongsTo(PrintOrder::class, 'order_id');
    }
}
