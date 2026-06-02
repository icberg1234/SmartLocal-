<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('floors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mall_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('level');
            $table->string('name');
            $table->string('map_svg_path')->nullable();
            $table->timestamps();

            $table->unique(['mall_id', 'level']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('floors');
    }
};
