<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shop_pricing', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('shop_id')->unique()->constrained()->cascadeOnDelete();
            $table->integer('black_and_white_per_page')->default(0);
            $table->integer('full_color_per_page')->default(0);
            $table->integer('double_side_surcharge')->default(0);
            $table->jsonb('binding_prices')->default('{}');
            $table->timestamp('updated_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_pricing');
    }
};
