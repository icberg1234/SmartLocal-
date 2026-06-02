<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('scope'); // marketing | data
            $table->timestamp('granted_at');

            $table->unique(['user_id', 'scope']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consents');
    }
};
