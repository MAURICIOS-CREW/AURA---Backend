<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\UserSession;
use Tymon\JWTAuth\Exceptions\JWTException;

class EnsureValidSession
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            // Verifica y decodifica el token sin llamar a la base de datos (solo la firma)
            $token = JWTAuth::parseToken();
            $payload = $token->getPayload();
            $jti = $payload->get('jti');

            // Ahora verificamos en la base de datos si la sesión es válida
            // (idealmente esto luego se pasaría a Caché/Redis para más velocidad)
            $session = UserSession::where('token_id', $jti)->first();

            if (!$session) {
                return response()->json(['error' => 'Sesión no encontrada'], 401);
            }

            if ($session->is_closed) {
                return response()->json(['error' => 'La sesión ha sido cerrada'], 401);
            }

            if ($session->expired_at && $session->expired_at <= now()) {
                return response()->json(['error' => 'La sesión ha expirado anticipadamente'], 401);
            }

            // Autenticar al usuario en la solicitud
            JWTAuth::authenticate();

        } catch (JWTException $e) {
            return response()->json(['error' => 'Token inválido o expirado'], 401);
        }

        return $next($request);
    }
}
