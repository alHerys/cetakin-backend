<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('atk_products', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('shop_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('price');
            $table->integer('stock')->default(0);
            $table->text('photo_url')->nullable();
            $table->boolean('is_available')->default(true);
            $table->timestamps();
        });

        DB::statement('CREATE INDEX idx_atk_products_shop_id ON atk_products(shop_id)');
    }

    public function down(): void
    {
        Schema::dropIfExists('atk_products');
    }
};
