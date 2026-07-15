<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class EnsureIsMobileUser
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response {
        /** @var \App\Models\User|null $user */
        $user = Auth::guard('api')->user();

        if (!$user || !$user->isMobileUser()) {
            return response()->json([
                'error' => 'No autorizado',
                'message' => 'Esta aplicación está restringida a residentes y personal autorizado de la app.'
            ], 403);
        }

        return $next($request);
    }
}
