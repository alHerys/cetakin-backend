<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('atk_order_status_history', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('order_id')->constrained('atk_orders')->cascadeOnDelete();
            $table->string('status', 30);
            $table->timestamp('created_at')->useCurrent();
        });

        DB::statement('CREATE INDEX idx_atk_status_history_order_id ON atk_order_status_history(order_id)');
    }

    public function down(): void
    {
        Schema::dropIfExists('atk_order_status_history');
    }
};
