<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_whitelist', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mall_id')->constrained()->cascadeOnDelete();
            $table->string('phone', 20);
            $table->timestamps();

            $table->unique(['mall_id', 'phone']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_whitelist');
    }
};
