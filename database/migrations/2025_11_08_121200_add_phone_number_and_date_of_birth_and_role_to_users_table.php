<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone_number')->nullable()->after('email');
            $table->date('date_of_birth')->nullable()->after('phone_number');
            // Role can be 'super_admin', 'branch_manager', 'customer', 'waiter', etc.
            $table->string('role')->default('customer')->after('date_of_birth');
            $table->boolean('is_active')->default(true)->after('role');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['phone_number', 'date_of_birth', 'role', 'is_active']);
        });
    }
};
