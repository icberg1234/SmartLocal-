<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Platform master data: SaaS packages. Runs before subscriptions (2025_01_03)
// so subscriptions.plan_id can reference it.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();                  // silver | gold | ...
            $table->string('name');
            $table->unsignedBigInteger('price')->default(0);  // Toman
            $table->unsignedInteger('store_quota')->default(0);
            $table->unsignedInteger('duration_days')->default(180);
            $table->json('features')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
