<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  ...$roles  (هنا يتم استقبال الأدوار الممررة من ملف الروابط)
     * @return mixed
     */
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $user = $request->user(); // جلب المستخدم المسجل دخول

        if (!$user) {
            return response()->json([
                'message' => 'يجب تسجيل الدخول أولاً'
            ], 401);
        }

        if (!in_array($user->role, $roles)) {
            return response()->json([
                'message' => 'Forbidden: غير مسموح لك بالوصول لهذه الصلاحية'
            ], 403);
        }

        return $next($request);
    }
}