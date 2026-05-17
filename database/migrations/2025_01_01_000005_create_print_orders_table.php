<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('print_orders', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('user_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('shop_id')->constrained()->restrictOnDelete();
            $table->text('file_url');
            $table->string('paper_size', 10);
            $table->string('color_mode', 20);
            $table->string('sides', 10);
            $table->string('binding', 20);
            $table->integer('copies')->default(1);
            $table->integer('total_pages')->default(0);
            $table->integer('final_price')->default(0);
            $table->text('notes')->nullable();
            $table->string('status', 30)->default('pending');
            $table->timestamps();
        });

        DB::statement("ALTER TABLE print_orders ADD CONSTRAINT chk_print_orders_paper_size CHECK (paper_size IN ('A4', 'A3', 'F4'))");
        DB::statement("ALTER TABLE print_orders ADD CONSTRAINT chk_print_orders_color_mode CHECK (color_mode IN ('black_and_white', 'full_color'))");
        DB::statement("ALTER TABLE print_orders ADD CONSTRAINT chk_print_orders_sides CHECK (sides IN ('single', 'double'))");
        DB::statement("ALTER TABLE print_orders ADD CONSTRAINT chk_print_orders_binding CHECK (binding IN ('none', 'staple', 'spiral'))");
        DB::statement("ALTER TABLE print_orders ADD CONSTRAINT chk_print_orders_status CHECK (status IN ('pending', 'confirmed', 'processing', 'ready_for_pickup', 'completed', 'cancelled'))");

        DB::statement('CREATE INDEX idx_print_orders_user_id ON print_orders(user_id)');
        DB::statement('CREATE INDEX idx_print_orders_shop_id ON print_orders(shop_id)');
        DB::statement('CREATE INDEX idx_print_orders_status ON print_orders(status)');
    }

    public function down(): void
    {
        Schema::dropIfExists('print_orders');
    }
};
