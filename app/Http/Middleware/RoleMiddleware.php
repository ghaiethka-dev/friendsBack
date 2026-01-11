<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $user = $request->user();

        // إذا لم يكن مسجّل دخول
        if (! $user) {
            return response()->json([
                'message' => 'Unauthenticated'
            ], 401);
        }

        // إذا لم يكن لديه الدور المطلوب
        if (! in_array($user->role, $roles)) {
            return response()->json([
                'message' => 'Forbidden'
            ], 403);
        }

        return $next($request);
    }
}
