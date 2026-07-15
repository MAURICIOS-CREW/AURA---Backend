<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\BannedUser;
use Illuminate\Support\Facades\Auth;

class EnsureNotBanned
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::guard('api')->user();

        if ($user) {
            $ban = BannedUser::where('user_id', $user->id)->first();
            
            if ($ban) {
                return response()->json([
                    'error' => 'Acceso denegado. Estás baneado.',
                    'reason' => $ban->reason
                ], 403);
            }
        }

        return $next($request);
    }
}
