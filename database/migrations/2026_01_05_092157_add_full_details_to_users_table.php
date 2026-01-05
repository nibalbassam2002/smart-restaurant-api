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
                $table->string('job_title')->nullable()->after('role');
            $table->string('department')->nullable()->after('job_title');
            
            // الصورة (مثل اللوجو تبع الفرع)
            $table->string('photo')->nullable()->after('name'); 
            
            // البيانات المالية والشخصية
            $table->string('address')->nullable();
            $table->decimal('salary', 8, 2)->nullable(); // الراتب
            $table->date('date_of_hire')->nullable(); // تاريخ التعيين
            
            // ربط الفرع (إذا لم يكن مضافاً)
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
            $table->dropColumn(['job_title', 'department', 'photo', 'address', 'salary', 'date_of_hire', 'branch_id']);
        });
    }
};
