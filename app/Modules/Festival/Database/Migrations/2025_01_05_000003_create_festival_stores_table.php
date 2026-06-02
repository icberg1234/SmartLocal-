<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('festival_stores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('festival_id')->constrained()->cascadeOnDelete();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('invited'); // invited | joined | declined
            $table->timestamps();

            $table->unique(['festival_id', 'store_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('festival_stores');
    }
};
