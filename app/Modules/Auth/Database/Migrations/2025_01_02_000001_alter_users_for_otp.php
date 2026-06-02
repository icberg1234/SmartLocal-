<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // OTP-first identity: phone is the unique login key.
            $table->string('phone', 20)->nullable()->unique()->after('id');
            $table->string('type')->default('customer')->after('phone'); // customer|business-owner|cashier|mall-manager|super-admin
            $table->string('status')->default('active')->after('type');

            // email/password/name are optional for OTP users.
            $table->string('name')->nullable()->change();
            $table->string('email')->nullable()->change();
            $table->string('password')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['phone']);
            $table->dropColumn(['phone', 'type', 'status']);
        });
    }
};
