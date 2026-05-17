<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shop_services', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('shop_id')->unique()->constrained()->cascadeOnDelete();
            $table->timestamp('updated_at')->useCurrent();
        });

        DB::statement("ALTER TABLE shop_services ADD COLUMN paper_sizes TEXT[] NOT NULL DEFAULT '{}'");
        DB::statement("ALTER TABLE shop_services ADD COLUMN color_modes TEXT[] NOT NULL DEFAULT '{}'");
        DB::statement("ALTER TABLE shop_services ADD COLUMN sides TEXT[] NOT NULL DEFAULT '{}'");
        DB::statement("ALTER TABLE shop_services ADD COLUMN bindings TEXT[] NOT NULL DEFAULT '{}'");
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_services');
    }
};
