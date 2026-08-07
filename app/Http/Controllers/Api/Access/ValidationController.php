<?php

namespace App\Http\Controllers\Api\Access;

use App\Http\Controllers\Controller;
use App\Models\AccessCode;
use App\Models\AccessLog;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ValidationController extends Controller
{
    public function validateAccess(Request $request)
    {
        $request->validate([
            'hash' => 'required|string',
            'device_identifier' => 'nullable|string',
        ]);

        $hash = $request->hash;
        $deviceId = $request->device_identifier;
        
        $accessCode = AccessCode::withTrashed()->where('code', $hash)->first();

        if (!$accessCode) {
            return $this->logAndRespond($hash, null, 'denied', 'Código no encontrado', $deviceId, 404);
        }

        if ($accessCode->trashed()) {
            return $this->logAndRespond($hash, $accessCode, 'denied', 'Código inhabilitado (eliminado)', $deviceId, 403);
        }

        if (!$accessCode->is_active) {
            return $this->logAndRespond($hash, $accessCode, 'denied', 'Código temporalmente inhabilitado', $deviceId, 403);
        }

        $now = Carbon::now();

        // 1. Rango de Fechas
        if ($accessCode->valid_from && $now->lt($accessCode->valid_from)) {
            $msg = 'Aún no es válido el código. Será válido a partir del ' . $accessCode->valid_from->format('Y-m-d H:i:s');
            return $this->logAndRespond($hash, $accessCode, 'denied', $msg, $deviceId, 403);
        }

        if ($accessCode->valid_until && $now->gt($accessCode->valid_until)) {
            $msg = 'Código expirado. Expiró el ' . $accessCode->valid_until->format('Y-m-d H:i:s');
            return $this->logAndRespond($hash, $accessCode, 'denied', $msg, $deviceId, 403);
        }
        
        // 2. Límite de Usos
        if (!is_null($accessCode->max_uses) && $accessCode->uses >= $accessCode->max_uses) {
            $msg = 'Límite de usos alcanzado, usos: ' . $accessCode->uses . ', máximo: ' . $accessCode->max_uses;
            return $this->logAndRespond($hash, $accessCode, 'denied', $msg, $deviceId, 403);
        }
        
        // Tipo temporal antiguo (type = 'temp'). 
        if ($accessCode->type === 'temp' && $accessCode->uses >= 1) {
             $msg = 'Código temporal ya utilizado, usos: ' . $accessCode->uses . ', máximo permitido: 1';
             return $this->logAndRespond($hash, $accessCode, 'denied', $msg, $deviceId, 403);
        }

        // 3. Días de la semana
        if (!empty($accessCode->active_days)) {
            $currentDay = $now->dayOfWeekIso; 
            if (!in_array($currentDay, $accessCode->active_days)) {
                $msg = 'Día no autorizado. El código sólo es válido los días: ' . implode(', ', $accessCode->active_days);
                return $this->logAndRespond($hash, $accessCode, 'denied', $msg, $deviceId, 403);
            }
        }

        // 4. Rango de Horas
        if ($accessCode->start_time && $accessCode->end_time) {
            $currentTime = $now->format('H:i:s');
            if ($currentTime < $accessCode->start_time || $currentTime > $accessCode->end_time) {
                $msg = 'Horario no autorizado. Horario de acceso válido: de ' . $accessCode->start_time . ' a ' . $accessCode->end_time;
                return $this->logAndRespond($hash, $accessCode, 'denied', $msg, $deviceId, 403);
            }
        }

        // Todo Ok
        $accessCode->increment('uses');
        
        return $this->logAndRespond($hash, $accessCode, 'granted', 'Acceso permitido', $deviceId, 200);
    }

    private function logAndRespond(
        string $hash, ?AccessCode $accessCode, string $status, string $message, ?string $deviceId, int $httpStatus
        ): \Illuminate\Http\JsonResponse {
        AccessLog::create([
            'access_code_id' => $accessCode ? $accessCode->id : null,
            'scanned_code' => $hash,
            'status' => $status,
            'message' => $message,
            'residence_id' => $accessCode ? $accessCode->residence_id : null,
            'device_identifier' => $deviceId,
            'access_type' => 'qr', // Mandatory field in DB
            'method' => 'scan',    // Mandatory field in DB
        ]);

        // Despachar el Job para notificar asíncronamente
        \App\Jobs\SendAlertJob::dispatch(
            $accessCode ? $accessCode->id : null,
            $hash,
            $status,
            $message,
            $deviceId
        );

        return response()->json([
            'status' => $status,
            'message' => $message,
            'data' => $accessCode ? [
                'guest_name' => $accessCode->guest_name,
                'residence_id' => $accessCode->residence_id
            ] : null
        ], $httpStatus);
    }
}
