<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class EnsureIsAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::guard('api')->user();

        if (!$user || !$user->isAdmin()) {
            return response()->json([
                'error' => 'No autorizado',
                'message' => 'El acceso a este recurso requiere privilegios de administrador.'
            ], 403);
        }

        return $next($request);
    }
}
