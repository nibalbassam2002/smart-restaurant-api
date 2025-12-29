<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;
use Illuminate\Routing\Router;
use Illuminate\Http\Request;
use Illuminate\Auth\AuthenticationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Illuminate\Validation\ValidationException;
use Throwable;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        using: function (Router $router) {
            $router->middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        
        // 1. منع التحويل لصفحة Login إذا كان الرابط API
        // (هذا يحل مشكلة Route [login] not defined)
        $middleware->redirectGuestsTo(function (Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return null; // يرجع 401 تلقائياً
            }
            return route('login');
        });

        // 2. تسجيل الاسم المستعار (Alias) للـ Middleware الخاص بالأدمن
        $middleware->alias([
            'is_admin' => \App\Http\Middleware\IsAdmin::class,
        ]);
        
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        
        // 🛑 معالجة خطأ 401 (غير مسجل دخول - Unauthenticated)
        $exceptions->renderable(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthenticated. Invalid or missing token.',
                ], 401);
            }
        });

        // 🛑 معالجة خطأ 403 (ليس لديك صلاحية - Forbidden)
        // هذا يظهر مثلاً لو موظف حاول يدخل صفحة سوبر أدمن
        $exceptions->renderable(function (AccessDeniedHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'status' => false,
                    'message' => 'Access Denied. You do not have permission.',
                ], 403);
            }
        });

        // 🛑 معالجة خطأ 404 (الرابط غير موجود - Not Found)
        // هذا يظهر لو طلبت رابط خطأ أو ID غير موجود
        $exceptions->renderable(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'status' => false,
                    'message' => 'The requested endpoint or resource was not found.',
                ], 404);
            }
        });

        // 🛑 معالجة أخطاء التحقق (Validation Error 422)
        // عشان يرجع JSON مرتب بدل الشكل الافتراضي
        $exceptions->renderable(function (ValidationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation Error.',
                    'errors' => $e->errors(),
                ], 422);
            }
        });

        // 🛑 معالجة خطأ السيرفر العام (500 Server Error)
        // يمسك أي خطأ في الكود ويرجع رسالة نظيفة بدل ما يعرض الكود للمستخدم
        $exceptions->renderable(function (Throwable $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'status' => false,
                    'message' => 'Server Error. Please try again later.',
                    // 'error' => $e->getMessage(), // فعلي هذا السطر فقط أثناء التطوير لترين سبب الخطأ
                ], 500);
            }
        });

    })->create();