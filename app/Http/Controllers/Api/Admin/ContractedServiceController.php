<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccessCode;
use App\Models\ContractedService;
use App\Models\FinancialCharge;
use App\Models\Payment;
use App\Models\Service;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ContractedServiceController extends Controller
{
    public function index(Request $request)
    {
        $query = ContractedService::with([
            'service',
            'user:id,name,username,email,phone',
            'residence.address',
            'financialCharge.payments',
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
            'financialCharge.payments',
            'accessCode',
        ]);

        return response()->json([
            'status' => 'success',
            'data' => $contractedService,
        ]);
    }

    /**
     * Asignar un servicio a un residente por parte de administración (a diferencia de
     * "contratar", que es el flujo self-service desde la app móvil). Queda agendado con
     * horario exacto y QR generado en el mismo paso, y opcionalmente ya cobrado.
     */
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'residence_id' => 'required|exists:residences,id',
            'service_id' => 'required|exists:services,id',
            'preferred_date' => 'required|date',
            'visit_time_from' => 'required|date_format:H:i',
            'visit_time_to' => 'required|date_format:H:i|after:visit_time_from',
            'notes' => 'nullable|string',
            'mark_as_paid' => 'nullable|boolean',
            'payment_method' => 'required_if:mark_as_paid,true|in:cash,transfer,card,check',
        ]);

        $resident = User::findOrFail($request->user_id);
        abort_unless(
            $resident->residences()->where('residences.id', $request->residence_id)->exists(),
            422,
            'La residencia seleccionada no pertenece a este residente.'
        );

        $service = Service::findOrFail($request->service_id);
        abort_unless($service->is_active, 422, 'Este servicio no se encuentra activo actualmente.');

        $markAsPaid = $request->boolean('mark_as_paid');

        $contractedService = DB::transaction(function () use ($request, $service, $markAsPaid) {
            $now = Carbon::now();

            $charge = FinancialCharge::create([
                'residence_id' => $request->residence_id,
                'amount' => $service->price,
                'month' => $now->month,
                'year' => $now->year,
                'status' => $markAsPaid ? 'paid' : 'pending',
            ]);

            if ($markAsPaid) {
                Payment::create([
                    'charge_id' => $charge->id,
                    'user_id' => $request->user_id,
                    'amount' => $service->price,
                    'payment_method' => $request->payment_method,
                    'status' => 'approved',
                    'validator_admin_id' => $request->user()->id,
                ]);
            }

            $contractedService = ContractedService::create([
                'service_id' => $service->id,
                'user_id' => $request->user_id,
                'residence_id' => $request->residence_id,
                'charge_id' => $charge->id,
                'preferred_date' => $request->preferred_date,
                'visit_time_from' => $request->visit_time_from,
                'visit_time_to' => $request->visit_time_to,
                'amount' => $service->price,
                'status' => 'created',
                'notes' => $request->notes,
                'payment_method' => $markAsPaid ? $request->payment_method : null,
            ]);

            $exactScheduledAt = Carbon::parse($request->preferred_date . ' ' . $request->visit_time_from);
            $this->applySchedule($contractedService, $exactScheduledAt, $request->notes);

            return $contractedService;
        });

        $contractedService->load([
            'service',
            'user:id,name,username,email,phone',
            'residence.address',
            'financialCharge.payments',
            'accessCode',
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Servicio asignado y programado correctamente.',
            'data' => $contractedService,
        ], 201);
    }

    public function schedule(Request $request, ContractedService $contractedService)
    {
        $request->validate([
            'exact_scheduled_at' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        $this->applySchedule($contractedService, Carbon::parse($request->exact_scheduled_at), $request->notes);

        $contractedService->load(['service', 'user', 'residence', 'financialCharge.payments', 'accessCode']);

        return response()->json([
            'status' => 'success',
            'message' => 'Servicio programado correctamente y código QR generado.',
            'data' => $contractedService,
        ]);
    }

    /**
     * Marca la contratación como agendada y crea/actualiza su AccessCode (QR). Compartido
     * entre store() (asignación directa por admin) y schedule() (agendar una contratación
     * que ya existía sin horario, típicamente originada desde la app móvil).
     */
    private function applySchedule(ContractedService $contractedService, Carbon $scheduledAt, ?string $notes = null): void
    {
        $contractedService->update([
            'exact_scheduled_at' => $scheduledAt,
            'status' => 'scheduled',
            'notes' => $notes ?: $contractedService->notes,
        ]);

        $randomData = Str::random(40) . $contractedService->user_id . uniqid('srv_', true);
        $codeHash = hash('sha256', $randomData);

        AccessCode::updateOrCreate(
            ['contracted_service_id' => $contractedService->id],
            [
                'residence_id' => $contractedService->residence_id,
                'user_id' => $contractedService->user_id,
                'guest_name' => 'Servicio: ' . ($contractedService->service ? $contractedService->service->title : 'Contratado'),
                'code' => $codeHash,
                'type' => 'service',
                'valid_from' => $scheduledAt->copy()->subHours(1),
                'valid_until' => $scheduledAt->copy()->addHours(6),
                'uses' => 0,
                'max_uses' => 10,
                'is_active' => true,
            ]
        );
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

        $contractedService->load(['service', 'user', 'residence', 'financialCharge.payments', 'accessCode']);

        return response()->json([
            'status' => 'success',
            'message' => "Estado del servicio actualizado a '{$newStatus}'.",
            'data' => $contractedService,
        ]);
    }
}
