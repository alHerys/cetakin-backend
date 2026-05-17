<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('print_order_status_history', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('order_id')->constrained('print_orders')->cascadeOnDelete();
            $table->string('status', 30);
            $table->timestamp('created_at')->useCurrent();
        });

        DB::statement('CREATE INDEX idx_print_status_history_order_id ON print_order_status_history(order_id)');
    }

    public function down(): void
    {
        Schema::dropIfExists('print_order_status_history');
    }
};
