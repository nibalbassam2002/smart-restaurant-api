<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\SocialAuthController;
use App\Http\Controllers\BranchController; 

/*
|--------------------------------------------------------------------------
| Public Routes (متاحة للجميع)
|--------------------------------------------------------------------------
*/
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

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
| Protected Routes (يجب أن يكون مسجل دخول)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    // أي مستخدم مسجل دخول يستطيع عمل خروج أو مشاهدة بياناته
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});

/*
|--------------------------------------------------------------------------
| SUPER ADMIN Routes (فقط السوبر أدمن)
|--------------------------------------------------------------------------
| هنا وضعنا الميدلير الجديد 'is_admin' الذي أنشأناه
*/
Route::middleware(['auth:sanctum', 'is_admin'])->group(function () {
    
    // 1. تهيئة صفحة إنشاء المطعم (جلب المدراء)
    Route::get('/branches', [BranchController::class, 'index']); 
    Route::get('/branches/create', [BranchController::class, 'create']);

    // 2. حفظ المطعم الجديد
    Route::post('/branches', [BranchController::class, 'store']);
    Route::get('/branches/{id}', [BranchController::class, 'show']);
    Route::put('/branches/{id}', [BranchController::class, 'update']);
    Route::get('/branches/{id}/employees', [BranchController::class, 'listEmployees']);
    // رابط حذف الفرع
    Route::delete('/branches/{id}', [BranchController::class, 'destroy']);

});

/*
|--------------------------------------------------------------------------
| System Helper Routes (مؤقتة للصيانة)
|--------------------------------------------------------------------------
*/
// هذا الراوت لتحديث الداتابيز على Render
Route::get('/update-db', function() {
    \Illuminate\Support\Facades\Artisan::call('migrate:refresh --seed --force');
    return 'Database Updated With New Fields (Notes, Location Details) & Seeded!';
});