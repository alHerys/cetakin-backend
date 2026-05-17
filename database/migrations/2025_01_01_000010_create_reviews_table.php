<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('user_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('shop_id')->constrained()->cascadeOnDelete();
            $table->string('order_type', 10);
            $table->foreignUuid('print_order_id')->nullable()->constrained('print_orders')->nullOnDelete();
            $table->foreignUuid('atk_order_id')->nullable()->constrained('atk_orders')->nullOnDelete();
            $table->smallInteger('rating');
            $table->text('comment')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        DB::statement("ALTER TABLE reviews ADD CONSTRAINT chk_reviews_order_type CHECK (order_type IN ('print', 'atk'))");
        DB::statement('ALTER TABLE reviews ADD CONSTRAINT chk_reviews_rating CHECK (rating BETWEEN 1 AND 5)');
        DB::statement('ALTER TABLE reviews ADD CONSTRAINT unique_print_order_review UNIQUE (print_order_id)');
        DB::statement('ALTER TABLE reviews ADD CONSTRAINT unique_atk_order_review UNIQUE (atk_order_id)');
        DB::statement("
            ALTER TABLE reviews ADD CONSTRAINT chk_order_reference CHECK (
                (order_type = 'print' AND print_order_id IS NOT NULL AND atk_order_id IS NULL) OR
                (order_type = 'atk'   AND atk_order_id IS NOT NULL  AND print_order_id IS NULL)
            )
        ");

        DB::statement('CREATE INDEX idx_reviews_shop_id ON reviews(shop_id)');
        DB::statement('CREATE INDEX idx_reviews_user_id ON reviews(user_id)');
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
