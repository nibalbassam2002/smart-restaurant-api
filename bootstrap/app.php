<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;
use Illuminate\Routing\Router;
use Illuminate\Http\Request;
use Illuminate\Auth\AuthenticationException;

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
        
        // --- التعديل الجذري هنا ---
        // 1. منع التحويل لصفحة Login إذا كان الرابط API
        $middleware->redirectGuestsTo(function (Request $request) {
            // إذا كان الطلب يتوقع JSON **أو** الرابط يبدأ بـ api
            if ($request->expectsJson() || $request->is('api/*')) {
                return null; // يرجع null فيقوم لارفيل بإرجاع خطأ 401 تلقائياً
            }
            return route('login'); // فقط للمتصفح العادي
        });
        // -------------------------

        // 2. تفعيل الاسم المستعار is_admin
        $middleware->alias([
            'is_admin' => \App\Http\Middleware\IsAdmin::class,
        ]);
        
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->renderable(function (AuthenticationException $e, Request $request) {
            // توحيد شكل رسالة الخطأ في الـ API
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthenticated. Invalid or missing token.',
                ], 401);
            }
        });
    })->create();