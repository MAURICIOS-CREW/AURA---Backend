<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccessCode;
use App\Models\ContractedService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ContractedServiceController extends Controller
{
    public function index(Request $request)
    {
        $query = ContractedService::with([
            'service',
            'user:id,name,username,email,phone',
            'residence.address',
            'financialCharge',
            'accessCode',
        ]);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('residence_id')) {
            $query->where('residence_id', $request->residence_id);
        }

        $contracts = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'status' => 'success',
            'data' => $contracts,
        ]);
    }

    public function show(ContractedService $contractedService)
    {
        $contractedService->load([
            'service',
            'user:id,name,username,email,phone',
            'residence.address',
            'financialCharge',
            'accessCode',
        ]);

        return response()->json([
            'status' => 'success',
            'data' => $contractedService,
        ]);
    }

    public function schedule(Request $request, ContractedService $contractedService)
    {
        $request->validate([
            'exact_scheduled_at' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        $scheduledAt = Carbon::parse($request->exact_scheduled_at);

        $contractedService->update([
            'exact_scheduled_at' => $scheduledAt,
            'status' => 'scheduled',
            'notes' => $request->filled('notes') ? $request->notes : $contractedService->notes,
        ]);

        // Crear o actualizar el AccessCode (QR) para el servicio
        $randomData = Str::random(40) . $contractedService->user_id . uniqid('srv_', true);
        $codeHash = hash('sha256', $randomData);

        $validFrom = $scheduledAt->copy()->subHours(1);
        $validUntil = $scheduledAt->copy()->addHours(6);

        $accessCode = AccessCode::updateOrCreate(
            ['contracted_service_id' => $contractedService->id],
            [
                'residence_id' => $contractedService->residence_id,
                'user_id' => $contractedService->user_id,
                'guest_name' => 'Servicio: ' . ($contractedService->service ? $contractedService->service->title : 'Contratado'),
                'code' => $codeHash,
                'type' => 'service',
                'valid_from' => $validFrom,
                'valid_until' => $validUntil,
                'uses' => 0,
                'max_uses' => 10,
                'is_active' => true,
            ]
        );

        $contractedService->load(['service', 'user', 'residence', 'financialCharge', 'accessCode']);

        return response()->json([
            'status' => 'success',
            'message' => 'Servicio programado correctamente y código QR generado.',
            'data' => $contractedService,
        ]);
    }

    public function updateStatus(Request $request, ContractedService $contractedService)
    {
        $request->validate([
            'status' => 'required|in:created,scheduled,in_progress,completed,refunded,cancelled',
            'notes' => 'nullable|string',
        ]);

        $newStatus = $request->status;

        $contractedService->status = $newStatus;
        if ($request->filled('notes')) {
            $contractedService->notes = $request->notes;
        }
        $contractedService->save();

        // Si se marca como reembolsado (refunded) o cancelado, sincronizar con el FinancialCharge
        if ($newStatus === 'refunded' && $contractedService->financialCharge) {
            $contractedService->financialCharge->update(['status' => 'refunded']);
        } elseif ($newStatus === 'cancelled' && $contractedService->financialCharge) {
            $contractedService->financialCharge->update(['status' => 'cancelled']);
        }

        $contractedService->load(['service', 'user', 'residence', 'financialCharge', 'accessCode']);

        return response()->json([
            'status' => 'success',
            'message' => "Estado del servicio actualizado a '{$newStatus}'.",
            'data' => $contractedService,
        ]);
    }
}
