<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CityAdminOnly
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user(); // المستخدم المسجل دخول

        // إذا لم يكن مسجّل دخول
        if (!$user) {
            return response()->json([
                'message' => 'يجب تسجيل الدخول أولاً'
            ], 401);
        }

        // إذا لم يكن الدور city_admin
        if ($user->role !== 'city_admin') {
            return response()->json([
                'message' => 'غير مسموح لك بتنفيذ هذه العملية'
            ], 403);
        }

        return $next($request);
    }
}
