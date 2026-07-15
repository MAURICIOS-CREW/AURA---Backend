<?php

namespace App\Http\Controllers\Api;

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
    public function login(Request $request)
    {
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

        // Verificar si está baneado
        $ban = BannedUser::where('user_id', $user->id)->first();
        if ($ban) {
            return response()->json([
                'error' => 'Usuario baneado',
                'reason' => $ban->reason
            ], 403);
        }

        // Generar token Sanctum
        $tokenResult = $user->createToken('session', ['access-api'], now()->addHours(3));

        return response()->json([
            'access_token' => $tokenResult->plainTextToken,
            'token_type' => 'bearer',
            'expires_in' => 10800,
            'user' => $user
        ]);
    }
}
