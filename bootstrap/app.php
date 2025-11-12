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
        using: function (Router $router) { // <--- لاحظي أنني استخدمت Router
            $router->middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo(function (Request $request) {
            // إذا كان الطلب يتوقع استجابة JSON (أي أنه طلب API)، لا تقومي بإعادة توجيه.
            // بدلاً من ذلك، ستقوم الـ exception handler الخاص بكِ بإرجاع 401.
            return $request->expectsJson() ? null : route('login');
        });

        // يمكنكِ هنا إضافة middleware أخرى إذا احتجتِ
        // $middleware->web(append: [
        //     \App\Http\Middleware\HandleInertiaRequests::class,
        //     \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        // ]);

        // $middleware->alias([
        //     'is_admin' => \App\Http\Middleware\IsAdmin::class,
        // ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->renderable(function (AuthenticationException $e, Request $request) {
            // تحقق إذا كان الطلب موجهاً إلى API أو يتوقع استجابة JSON
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'message' => 'Unauthenticated. Invalid or missing token.',
                ], 401);
            }
        });
    })->create();
