<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('mall_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('visit_count')->default(0);
            $table->unsignedBigInteger('total_spent')->default(0);
            $table->timestamp('last_visit_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'mall_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_profiles');
    }
};
