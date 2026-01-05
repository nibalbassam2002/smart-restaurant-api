<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\SocialAuthController;
use App\Http\Controllers\BranchController; 
use App\Http\Controllers\NewPasswordController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\AttendanceController;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [NewPasswordController::class, 'forgotPassword']);
Route::post('/reset-password', [NewPasswordController::class, 'reset']);

/*
|--------------------------------------------------------------------------
| Social Auth Routes
|--------------------------------------------------------------------------
*/
Route::middleware('web')->group(function () {
    Route::get('auth/{provider}/redirect', [SocialAuthController::class, 'redirect']);
    Route::get('auth/{provider}/callback', [SocialAuthController::class, 'callback']);
});

/*
|--------------------------------------------------------------------------
| Protected Routes
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});

/*
|--------------------------------------------------------------------------
| SUPER ADMIN Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'is_admin'])->group(function () {
    
    // --- إدارة الفروع (Branches) ---
    Route::get('/branches', [BranchController::class, 'index']); 
    Route::get('/branches/create', [BranchController::class, 'create']);
    Route::post('/branches', [BranchController::class, 'store']);
    Route::get('/branches/{id}', [BranchController::class, 'show']);
    Route::put('/branches/{id}', [BranchController::class, 'update']);
    Route::delete('/branches/{id}', [BranchController::class, 'destroy']);

    // --- إدارة الموظفين (Employees) ---
    Route::get('/employees', [EmployeeController::class, 'getAllEmployees']);
    Route::get('/branches/{branchId}/employees', [EmployeeController::class, 'index']);
    Route::post('/employees', [EmployeeController::class, 'store']);
    Route::get('/employees/{id}', [EmployeeController::class, 'show']);
    Route::put('/employees/{id}', [EmployeeController::class, 'update']);
    Route::delete('/employees/{id}', [EmployeeController::class, 'destroy']);
    
    // --- الحضور والغياب (مشاهدة فقط) ---
    Route::get('/employees/{id}/attendance', [AttendanceController::class, 'getEmployeeAttendance']);

});

/*
|--------------------------------------------------------------------------
| System Helper Routes (زر التحديث السحري)
|--------------------------------------------------------------------------
*/
Route::get('/update-db', function() {
    // استخدمنا fresh بدلاً من refresh
    // fresh: يحذف الجداول فوراً دون النظر لدالة down
    \Illuminate\Support\Facades\Artisan::call('migrate:fresh --seed --force');
    
    // إعادة تشغيل السيدر الإضافي للحضور
    \Illuminate\Support\Facades\Artisan::call('db:seed --class=AttendanceSeeder --force');

    return 'Database Wiped & Re-Created Successfully with Full Details!';
});