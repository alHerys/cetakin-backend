<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('atk_order_items', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('atk_order_id')->constrained('atk_orders')->cascadeOnDelete();
            $table->foreignUuid('atk_product_id')->constrained('atk_products')->restrictOnDelete();
            $table->string('name');
            $table->integer('quantity')->default(1);
            $table->integer('unit_price');
            $table->integer('subtotal');
        });

        DB::statement('CREATE INDEX idx_atk_order_items_order_id ON atk_order_items(atk_order_id)');
    }

    public function down(): void
    {
        Schema::dropIfExists('atk_order_items');
    }
};
