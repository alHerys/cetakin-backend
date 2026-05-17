<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('atk_orders', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('user_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('shop_id')->constrained()->restrictOnDelete();
            $table->integer('final_price')->default(0);
            $table->text('notes')->nullable();
            $table->string('status', 30)->default('pending');
            $table->timestamps();
        });

        DB::statement("ALTER TABLE atk_orders ADD CONSTRAINT chk_atk_orders_status CHECK (status IN ('pending', 'confirmed', 'processing', 'ready_for_pickup', 'completed', 'cancelled'))");

        DB::statement('CREATE INDEX idx_atk_orders_user_id ON atk_orders(user_id)');
        DB::statement('CREATE INDEX idx_atk_orders_shop_id ON atk_orders(shop_id)');
        DB::statement('CREATE INDEX idx_atk_orders_status ON atk_orders(status)');
    }

    public function down(): void
    {
        Schema::dropIfExists('atk_orders');
    }
};
