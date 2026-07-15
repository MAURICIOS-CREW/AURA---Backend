<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireJsonHeader
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Require Accept: application/json for all API endpoints
        if (! str_contains($request->header('Accept'), 'application/json')) {
            return response()->json([
                'status' => 'error',
                'message' => 'Bad Request. The "Accept: application/json" header is missing and is required for all API requests.'
            ], 400);
        }

        return $next($request);
    }
}
