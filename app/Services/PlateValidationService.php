<?php

namespace App\Services;

use App\Contracts\AccessNotifierInterface;
use App\Contracts\LicensePlateNormalizerInterface;
use App\Models\AccessLog;
use App\Models\Vehicle;

class PlateValidationService
{
    public function __construct(
        protected LicensePlateNormalizerInterface $normalizer,
        protected AccessNotifierInterface $notifier
    ) {}

    /**
     * Validate access by vehicle license plate.
     * @param string $rawPlate Raw incoming license plate
     * @param string|null $deviceId Scanner/camera device identifier
     * @return array Result array containing http_status, status, message, and data
     */
    public function validatePlate(string $rawPlate, ?string $deviceId = null): array
    {
        $normalizedInput = $this->normalizer->normalize($rawPlate);

        // 1. Search DB for matching vehicle (exact or normalized comparison)
        $vehicle = Vehicle::withTrashed()
            ->where('plate', $rawPlate)
            ->orWhere('plate', $normalizedInput)
            ->first();

        if (!$vehicle) {
            // Fallback: load vehicles and match using normalizer service
            $allVehicles = Vehicle::withTrashed()->get();
            $vehicle = $allVehicles->first(function ($v) use ($normalizedInput) {
                return $this->normalizer->normalize($v->plate) === $normalizedInput;
            });
        }

        // 2. Vehicle Not Found
        if (!$vehicle) {
            $status = 'denied';
            $message = 'Placa no encontrada';
            
            $this->logAndNotify($rawPlate, null, $status, $message, $deviceId);

            return [
                'http_status' => 404,
                'status'      => $status,
                'message'     => $message,
                'data'        => null,
            ];
        }

        // 3. Soft-deleted (Disabled) Vehicle
        if ($vehicle->trashed()) {
            $status = 'denied';
            $message = 'Vehículo inhabilitado (eliminado)';

            $this->logAndNotify($rawPlate, $vehicle, $status, $message, $deviceId);

            return [
                'http_status' => 403,
                'status'      => $status,
                'message'     => $message,
                'data'        => null,
            ];
        }

        // 4. Access Granted
        $status = 'granted';
        $message = 'Acceso permitido';
        
        $this->logAndNotify($rawPlate, $vehicle, $status, $message, $deviceId);

        $guestName = 'Vehículo ' . $vehicle->plate;

        return [
            'http_status' => 200,
            'status'      => $status,
            'message'     => $message,
            'data'        => [
                'guest_name'   => $guestName,
                'residence_id' => $vehicle->residence_id,
            ],
        ];
    }

    /**
     * Log access attempt to DB and dispatch standardized FCM & WebSocket notifications.
     */
    protected function logAndNotify(
        string $rawPlate,
        ?Vehicle $vehicle,
        string $status,
        string $message,
        ?string $deviceId
    ): void {
        $residenceId = $vehicle ? $vehicle->residence_id : null;
        $vehicleId   = $vehicle ? $vehicle->id : null;
        $guestName   = $vehicle ? 'Vehículo ' . $vehicle->plate : 'Vehículo ' . $rawPlate;

        AccessLog::create([
            'access_code_id'    => null,
            'vehicle_id'        => $vehicleId,
            'residence_id'      => $residenceId,
            'scanned_code'      => $rawPlate,
            'status'            => $status,
            'message'           => $message,
            'device_identifier' => $deviceId,
            'access_type'       => 'plate',
            'method'            => 'scan',
        ]);

        $this->notifier->notify([
            'access_type'       => 'plate',
            'method'            => 'scan',
            'status'            => $status,
            'message'           => $message,
            'scanned_code'      => $rawPlate,
            'device_identifier' => $deviceId,
            'residence_id'      => $residenceId,
            'vehicle_id'        => $vehicleId,
            'guest_name'        => $guestName,
            'data'              => [
                'plate' => $vehicle ? $vehicle->plate : $rawPlate,
                'brand' => $vehicle ? $vehicle->brand : null,
                'color' => $vehicle ? $vehicle->color : null,
            ],
        ]);
    }
}
