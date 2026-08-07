<?php

namespace App\Services;

use App\Contracts\AccessNotifierInterface;
use App\Events\AccessValidatedEvent;
use App\Jobs\SendAlertJob;

class AccessNotifierService implements AccessNotifierInterface
{
    /**
     * Notify access validation event across WebSockets and FCM Push Notifications
     * using a standardized data payload.
     *
     * @param array $payload Must include status, message, access_type, method, scanned_code, etc.
     * @return void
     */
    public function notify(array $payload): void
    {
        $status = $payload['status'] ?? 'denied';
        $message = $payload['message'] ?? '';
        $accessType = $payload['access_type'] ?? 'qr';
        $method = $payload['method'] ?? 'scan';
        $scannedCode = $payload['scanned_code'] ?? ($payload['hash'] ?? '');
        $deviceId = $payload['device_identifier'] ?? null;
        $residenceId = $payload['residence_id'] ?? null;
        $accessCodeId = $payload['access_code_id'] ?? null;
        $vehicleId = $payload['vehicle_id'] ?? null;
        $guestName = $payload['guest_name'] ?? ($payload['subject_name'] ?? null);
        $extraData = $payload['data'] ?? [];

        // 1. Dispatch Push Notification Job
        SendAlertJob::dispatch(
            $accessCodeId,
            $scannedCode,
            $status,
            $message,
            $deviceId,
            $residenceId,
            $accessType,
            $method,
            $guestName,
            is_array($extraData) ? $extraData : []
        );

        // 2. Build Standardized WebSockets Event Payload
        $websocketData = [
            'type'              => 'access_log',
            'status'            => $status,
            'message'           => $message,
            'timestamp'         => now()->toIso8601String(),
            'access_type'       => $accessType,
            'method'            => $method,
            'scanned_code'      => $scannedCode,
            'device_identifier' => $deviceId,
            'residence_id'      => $residenceId,
            'access_code_id'    => $accessCodeId,
            'vehicle_id'        => $vehicleId,
            'guest_name'        => $guestName,
            'data'              => $extraData
        ];

        // 3. Fire WebSocket Broadcast Event
        event(new AccessValidatedEvent($websocketData));
    }
}
