<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shops', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->string('shop_name');
            $table->text('shop_address');
            $table->string('shop_phone', 20)->nullable();
            $table->text('shop_description')->nullable();
            $table->text('shop_photo_url')->nullable();
            $table->time('open_time');
            $table->time('close_time');
            $table->string('status', 20)->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->decimal('average_rating', 3, 2)->default(0.00);
            $table->integer('total_reviews')->default(0);
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->timestamps();
        });

        DB::statement("ALTER TABLE shops ADD COLUMN operating_days TEXT[] NOT NULL DEFAULT '{}'");
        DB::statement("ALTER TABLE shops ADD CONSTRAINT chk_shops_status CHECK (status IN ('pending', 'approved', 'rejected'))");

        DB::statement('CREATE INDEX idx_shops_status ON shops(status)');
        DB::statement('CREATE INDEX idx_shops_location ON shops(latitude, longitude)');
    }

    public function down(): void
    {
        Schema::dropIfExists('shops');
    }
};
