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
            
            // 1. فحص وإضافة المسمى الوظيفي
            if (!Schema::hasColumn('users', 'job_title')) {
                $table->string('job_title')->nullable()->after('role');
            }

            // 2. فحص وإضافة القسم
            if (!Schema::hasColumn('users', 'department')) {
                $table->string('department')->nullable()->after('job_title');
            }

            // 3. فحص وإضافة الصورة
            if (!Schema::hasColumn('users', 'photo')) {
                $table->string('photo')->nullable()->after('name');
            }

            // 4. فحص وإضافة العنوان
            if (!Schema::hasColumn('users', 'address')) {
                $table->string('address')->nullable();
            }

            // 5. فحص وإضافة الراتب
            if (!Schema::hasColumn('users', 'salary')) {
                $table->decimal('salary', 8, 2)->nullable();
            }

            // 6. فحص وإضافة تاريخ التعيين
            if (!Schema::hasColumn('users', 'date_of_hire')) {
                $table->date('date_of_hire')->nullable();
            }

            // 7. فحص وإضافة رابط الفرع
            if (!Schema::hasColumn('users', 'branch_id')) {
                $table->foreignId('branch_id')->nullable()->after('id')->constrained('branches')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'job_title')) $table->dropColumn('job_title');
            if (Schema::hasColumn('users', 'department')) $table->dropColumn('department');
            if (Schema::hasColumn('users', 'photo')) $table->dropColumn('photo');
            if (Schema::hasColumn('users', 'address')) $table->dropColumn('address');
            if (Schema::hasColumn('users', 'salary')) $table->dropColumn('salary');
            if (Schema::hasColumn('users', 'date_of_hire')) $table->dropColumn('date_of_hire');
            if (Schema::hasColumn('users', 'branch_id')) {
                $table->dropForeign(['branch_id']);
                $table->dropColumn('branch_id');
            }
        });
    }
};
