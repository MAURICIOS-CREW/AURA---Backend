<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\AccessCode;
use App\Models\ContractedService;
use App\Models\FinancialCharge;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ContractedServiceController extends Controller
{
    public function contract(Request $request, Service $service)
    {
        if (!$service->is_active) {
            return response()->json([
                'status' => 'error',
                'message' => 'Este servicio no se encuentra activo actualmente.',
            ], 400);
        }

        $request->validate([
            'residence_id' => 'required|exists:residences,id',
            'preferred_date' => 'required|date|after_or_equal:today',
            'visit_time_from' => 'required|date_format:H:i',
            'visit_time_to' => 'required|date_format:H:i|after:visit_time_from',
            'is_recurrent' => 'nullable|boolean',
            'suggested_schedule' => 'nullable',
            'notes' => 'nullable|string',
            'payment_method' => 'nullable|string',
        ]);

        $user = $request->user();

        // Validar que el usuario sea propietario/residente de la residencia
        if (!$user->residences()->where('residences.id', $request->residence_id)->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'No tienes permisos para solicitar un servicio en esta residencia.',
            ], 403);
        }

        $now = Carbon::now();
        $isRecurrent = (bool) $request->boolean('is_recurrent');
        $suggestedSchedule = $request->suggested_schedule;

        // Crear el registro de cargo financiero (financial_charges)
        $charge = FinancialCharge::create([
            'residence_id' => $request->residence_id,
            'amount' => $service->price,
            'month' => $now->month,
            'year' => $now->year,
            'status' => 'paid', // Simulación de pago previo por Stripe
        ]);

        // Crear la contratación del servicio con estado inicial 'created'
        $contractedService = ContractedService::create([
            'service_id' => $service->id,
            'user_id' => $user->id,
            'residence_id' => $request->residence_id,
            'charge_id' => $charge->id,
            'preferred_date' => $request->preferred_date,
            'visit_time_from' => $request->visit_time_from,
            'visit_time_to' => $request->visit_time_to,
            'amount' => $service->price,
            'status' => 'created',
            'is_recurrent' => $isRecurrent,
            'suggested_schedule' => $suggestedSchedule,
            'notes' => $request->notes,
            'payment_method' => $request->payment_method ?? 'stripe',
        ]);

        if ($isRecurrent) {
            $randomData = Str::random(40) . $user->id . uniqid('srv_rec_', true);
            $codeHash = hash('sha256', $randomData);

            $validFrom = Carbon::parse($request->preferred_date)->startOfDay();
            $validUntil = Carbon::parse($request->preferred_date)->addYear()->endOfDay();

            AccessCode::create([
                'residence_id' => $request->residence_id,
                'contracted_service_id' => $contractedService->id,
                'user_id' => $user->id,
                'guest_name' => 'Servicio Recurrente: ' . $service->title,
                'code' => $codeHash,
                'type' => 'service',
                'valid_from' => $validFrom,
                'valid_until' => $validUntil,
                'uses' => 0,
                'max_uses' => null,
                'active_days' => is_array($suggestedSchedule) ? $suggestedSchedule : null,
                'start_time' => $request->visit_time_from,
                'end_time' => $request->visit_time_to,
                'is_active' => true,
            ]);
        }

        $contractedService->load(['service', 'residence', 'financialCharge', 'accessCode']);

        return response()->json([
            'status' => 'success',
            'message' => 'Servicio contratado exitosamente. En espera de asignación de horario exacto por administración.',
            'data' => $contractedService,
        ], 201);
    }

    public function index(Request $request)
    {
        $user = $request->user();

        $contractedServices = ContractedService::with([
            'service',
            'residence.address',
            'financialCharge',
            'accessCode',
        ])
        ->where('user_id', $user->id)
        ->orderBy('created_at', 'desc')
        ->get();

        return response()->json([
            'status' => 'success',
            'data' => $contractedServices,
        ]);
    }

    public function show(Request $request, ContractedService $contractedService)
    {
        if ($contractedService->user_id !== $request->user()->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'No autorizado.',
            ], 403);
        }

        $contractedService->load([
            'service',
            'residence.address',
            'financialCharge',
            'accessCode',
        ]);

        return response()->json([
            'status' => 'success',
            'data' => $contractedService,
        ]);
    }

    public function markCompleted(Request $request, ContractedService $contractedService)
    {
        if ($contractedService->user_id !== $request->user()->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'No autorizado.',
            ], 403);
        }

        if (in_array($contractedService->status, ['completed', 'cancelled', 'refunded'])) {
            return response()->json([
                'status' => 'error',
                'message' => "El servicio no puede ser finalizado desde su estado actual ('{$contractedService->status}').",
            ], 400);
        }

        $contractedService->update(['status' => 'completed']);
        $contractedService->load(['service', 'residence', 'financialCharge', 'accessCode']);

        return response()->json([
            'status' => 'success',
            'message' => 'El servicio ha sido marcado como finalizado.',
            'data' => $contractedService,
        ]);
    }
}
