<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;

// هذا الراوت سنحذفه بعد الاستخدام لأسباب أمنية
Route::get('/setup-admin', function () {
    try {
        // 1. تشغيل المايكريشن (لضمان وجود الجداول)
        Artisan::call('migrate --force');
        $migrationOutput = Artisan::output();

        // 2. تشغيل السيدر (لإضافة الأدمن)
        Artisan::call('db:seed --class=SuperAdminSeeder --force');
        $seedOutput = Artisan::output();

        return "<h1>Success!</h1><br>Migration: $migrationOutput <br> Seed: $seedOutput";
    } catch (\Exception $e) {
        return "<h1>Error!</h1>" . $e->getMessage();
    }
});