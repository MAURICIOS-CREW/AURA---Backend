<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AccessLog;
use App\Events\UserNotificationEvent;
use App\Events\AccessValidatedEvent;

class TestNotificationController extends Controller
{
    /**
     * Envía una notificación web de prueba sorteando entre los 5 accesos reales más recientes.
     */
    public function sendTestNotification(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'nullable|integer',
            'channel' => 'nullable|string',
        ]);

        $userId = $validated['user_id'] ?? $request->user()?->id;
        $channel = $validated['channel'] ?? 'access-logs';

        // Obtenemos los 5 accesos más recientes de la base de datos
        $recentLogs = AccessLog::with([
            'accessCode:id,guest_name',
            'residence:id,block',
            'vehicle:id,plate,brand,color'
        ])
        ->latest('id')
        ->take(5)
        ->get();

        if ($recentLogs->isNotEmpty()) {
            // Sortear uno entre los 5 más recientes
            $log = $recentLogs->random();
            $guestName = $log->accessCode->guest_name ?? 'Invitado';
            $blockName = $log->residence->block ?? 'General';
            $plateName = $log->vehicle->plate ?? null;

            $statusText = $log->status === 'granted' ? 'Permitido' : 'Denegado';
            $title = "Acceso {$statusText}: {$guestName}";
            $body = "Intento de acceso ({$log->access_type}) en manzana {$blockName}. " . ($log->message ?? '');

            $payload = [
                'id' => (string) $log->id,
                'access_log_id' => $log->id,
                'title' => $title,
                'body' => $body,
                'status' => $log->status,
                'access_type' => $log->access_type,
                'method' => $log->method,
                'message' => $log->message,
                'guest_name' => $guestName,
                'block' => $blockName,
                'plate' => $plateName,
                'scanned_code' => $log->scanned_code,
                'device_identifier' => $log->device_identifier,
                'timestamp' => $log->timestamp ?? now()->toIso8601String(),
                'type' => 'access_validated',
                'source' => 'real_access_log_picker',
            ];
        } else {
            // Mock fallback si la base de datos aún no tiene registros
            $payload = [
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'title' => 'Acceso Permitido: Juan Pérez (Demo)',
                'body' => 'Acceso peatonal mediante QR validado en Manzana A-12.',
                'status' => 'granted',
                'access_type' => 'qr',
                'method' => 'scan',
                'message' => 'Acceso validado correctamente',
                'guest_name' => 'Juan Pérez',
                'block' => 'Manzana A-12',
                'timestamp' => now()->toIso8601String(),
                'type' => 'access_validated',
                'source' => 'mock_fallback',
            ];
        }

        // Transmitir en vivo a través de WebSockets
        UserNotificationEvent::dispatch($payload, $userId, $channel);
        AccessValidatedEvent::dispatch($payload);

        return response()->json([
            'success' => true,
            'message' => 'Notificación de acceso simulada enviada exitosamente vía WebSocket.',
            'target_channel' => $userId ? "private-users.{$userId}" : "private-{$channel}",
            'randomized_from_recent_count' => $recentLogs->count(),
            'payload' => $payload,
        ]);
    }
}
