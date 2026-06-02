<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('type');                      // e.g. Scanned, Registered, RedemptionCompleted
            $table->nullableMorphs('actor');             // who did it (user/device)
            $table->nullableMorphs('subject');           // what it was about (store/festival...)
            $table->foreignId('mall_id')->nullable()->constrained()->nullOnDelete();
            $table->json('payload')->nullable();
            $table->unsignedSmallInteger('schema_version')->default(1);
            $table->timestamp('created_at')->nullable()->index();

            $table->index(['mall_id', 'type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
