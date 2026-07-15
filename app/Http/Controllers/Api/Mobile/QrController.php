<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\AccessCode;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class QrController extends Controller {
    /**
     * Genera o recupera un QR temporal para el usuario (residente).
     */
    public function getTempQr(Request $request){
        $user = $request->user();
        
        // Obtener la primera residencia del usuario. 
        // Si hay una lógica para elegir la activa, se puede ajustar aquí.
        $residence = $user->residences()->first();
        
        if (!$residence) {
            return response()->json([
                'status' => 'error',
                'message' => 'Usuario no tiene residencia asignada.'
            ], 403);
        }
        
        $now = Carbon::now();
        $guestName = $user->id . ' - ' . $user->name;
        
        // Buscar un código existente, válido, no expirado y sin usar para este usuario
        $existingCode = AccessCode::where('residence_id', $residence->id)
            ->where('type', 'temp')
            ->where('guest_name', $guestName)
            ->where('valid_until', '>', $now)
            ->where('uses', 0)
            ->first();
            
        if ($existingCode) {
            return response()->json([
                'status' => 'success',
                'data' => [
                    'hash' => $existingCode->code,
                    'expires_at' => $existingCode->valid_until->toIso8601String()
                ]
            ]);
        }

        // Generar un hash no reversible y seguro usando datos aleatorios + del usuario
        $randomData = Str::random(40) . $user->id . $user->email . uniqid('', true);
        $hash = hash('sha256', $randomData);
        
        // Expiración en 10 minutos
        $expiresAt = $now->copy()->addMinutes(10);
        
        AccessCode::create([
            'residence_id' => $residence->id,
            'guest_name' => $guestName,
            'code' => $hash,
            'type' => 'temp',
            'valid_from' => $now,
            'valid_until' => $expiresAt,
            'uses' => 0
        ]);
        
        return response()->json([
            'status' => 'success',
            'data' => [
                'hash' => $hash,
                'expires_at' => $expiresAt->toIso8601String()
            ]
        ]);
    }
}
