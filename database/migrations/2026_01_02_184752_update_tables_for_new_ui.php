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
                // 1. تعديل جدول الفروع (إضافة كود الفرع)
            Schema::table('branches', function (Blueprint $table) {
                if (!Schema::hasColumn('branches', 'branch_code')) {
                    $table->string('branch_code')->nullable()->unique()->after('id');
                }
            });

            // 2. تعديل جدول المستخدمين (إضافة الوظيفة والقسم)
            Schema::table('users', function (Blueprint $table) {
                if (!Schema::hasColumn('users', 'job_title')) {
                    $table->string('job_title')->nullable()->after('role');
                    $table->string('department')->nullable()->after('job_title');
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
            Schema::table('branches', function (Blueprint $table) {
            $table->dropColumn('branch_code');
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['job_title', 'department']);
        });
    }
};
