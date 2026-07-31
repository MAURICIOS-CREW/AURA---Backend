<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\UserSession;
use App\Models\BannedUser;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    public function login(Request $request) {
        $request->validate([
            'login' => 'required|string', // Puede ser email o username
            'password' => 'required|string',
        ]);

        $loginType = filter_var($request->login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        $user = User::where($loginType, $request->login)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['error' => 'Credenciales incorrectas'], 401);
        }

        // Verificar si el usuario está activo
        if (!$user->is_active) {
            return response()->json(['error' => 'Tu cuenta está inactiva. Por favor, contacta al administrador.'], 403);
        }

        // Validar que sea un usuario móvil (residente/guardia)
        if (!$user->isMobileUser()) {
            return response()->json(['error' => 'Este acceso es exclusivo para la aplicación móvil'], 403);
        }

        // Verificar si está baneado
        $ban = BannedUser::where('user_id', $user->id)->first();
        if ($ban) {
            return response()->json([
                'error' => 'Usuario baneado',
                'reason' => $ban->reason
            ], 403);
        }

        // Generar tokens Sanctum
        $accessTokenResult = $user->createToken('mobile-session', ['access-api'], now()->addHours(3));
        $refreshTokenResult = $user->createToken('mobile-refresh', ['issue-access-token'], now()->addDays(30));

        return response()->json([
            'access_token' => $accessTokenResult->plainTextToken,
            'refresh_token' => $refreshTokenResult->plainTextToken,
            'token_type' => 'bearer',
            'expires_in' => 10800,
            'user' => $user->load('role')
        ]);
    }

    /**
     * Endpoint para renovar la sesión (usando el refresh_token)
     */
    public function refresh(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = clone $request->user();

        // Validar que el token actual tenga la habilidad para refrescar
        if (!$user->currentAccessToken()->can('issue-access-token')) {
            return response()->json(['error' => 'El token provisto no es válido para renovar sesión'], 403);
        }

        // Generar un nuevo access token de 3 horas
        $newAccessTokenResult = $user->createToken('mobile-session', ['access-api'], now()->addHours(3));

        return response()->json([
            'access_token' => $newAccessTokenResult->plainTextToken,
            'token_type' => 'bearer',
            'expires_in' => 10800,
        ]);
    }

    /**
     * Endpoint para actualizar el token de FCM del dispositivo móvil
     */
    public function updateFcmToken(Request $request)
    {
        $request->validate([ 'fcm' => 'required|string', ]);

        /** @var \App\Models\User $user */
        $user = $request->user();
        
        $user->fcm_token = $request->fcm;
        $user->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Token FCM actualizado correctamente'
        ]);
    }
}
