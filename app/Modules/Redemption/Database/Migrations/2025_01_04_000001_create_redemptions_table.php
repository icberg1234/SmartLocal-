<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('redemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mall_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('nonce')->unique();              // anti-replay
            $table->unsignedBigInteger('amount');            // gross (Toman)
            $table->unsignedTinyInteger('discount_pct');
            $table->unsignedBigInteger('discount_amount');
            $table->unsignedBigInteger('final_amount');
            $table->unsignedInteger('points_awarded')->default(0);
            $table->timestamp('created_at')->nullable()->index();

            $table->index(['mall_id', 'store_id', 'created_at']);
            $table->index(['user_id', 'store_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('redemptions');
    }
};
