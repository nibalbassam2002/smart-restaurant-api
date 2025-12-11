<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsAdmin
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        
        if ($request->user() && $request->user()->role === 'super_admin') {
            return $next($request); 
        }
        return response()->json([
            'status' => false,
            'message' => 'Access Denied! You are not a Super Admin.'
        ], 403);
    }
}