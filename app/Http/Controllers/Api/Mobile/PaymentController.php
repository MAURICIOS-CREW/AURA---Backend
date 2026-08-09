<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\ContractedService;
use App\Models\FinancialCharge;
use App\Models\Payment;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PaymentController extends Controller
{
    /**
     * Obtener resumen de pagos, saldo pendiente y cargos históricos paginados.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $residenceIds = $user->residences->pluck('id');
        $now = Carbon::now();

        // 1. Obtener precio base de servicios recurrentes obligatorios
        $recurrentServicePrice = Service::where('is_recurrent', true)
            ->where('is_active', true)
            ->sum('price');

        if ($recurrentServicePrice <= 0) {
            $recurrentServicePrice = 200.00; 
        }

        $pendingItems = [];

        // 2. Verificar si ya existe cargo generado para el mes actual
        $currentMonthCharge = FinancialCharge::whereIn('residence_id', $residenceIds)
            ->where('month', $now->month)
            ->where('year', $now->year)
            ->first();

        if (!$currentMonthCharge) {
            // Si no existe registro del mes actual en DB, añadirlo como concepto pendiente virtual
            $pendingItems[] = [
                'id' => null,
                'type' => 'monthly_fee',
                'title' => "Mantenimiento Mensual - " . ucfirst($now->translatedFormat('F Y')),
                'amount' => number_format($recurrentServicePrice, 2, '.', ''),
                'month' => $now->month,
                'year' => $now->year,
                'status' => 'pending',
                'is_recurrent' => true,
                'contracted_service_id' => null,
                'created_at' => $now->startOfMonth()->toIso8601String(),
            ];
        }

        // 3. Obtener cargos financieros existentes en estado 'pending'
        $pendingCharges = FinancialCharge::with(['contractedService.service'])
            ->whereIn('residence_id', $residenceIds)
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->get();

        foreach ($pendingCharges as $charge) {
            $cs = $charge->contractedService;
            $title = $cs && $cs->service
                ? $cs->service->title
                : "Mantenimiento Mensual - {$charge->month}/{$charge->year}";

            $pendingItems[] = [
                'id' => $charge->id,
                'type' => $cs ? 'contracted_service' : 'financial_charge',
                'title' => $title,
                'amount' => number_format($charge->amount, 2, '.', ''),
                'month' => $charge->month,
                'year' => $charge->year,
                'status' => $charge->status,
                'is_recurrent' => $cs ? (bool) $cs->is_recurrent : true,
                'contracted_service_id' => $cs?->id,
                'created_at' => $charge->created_at?->toIso8601String(),
            ];
        }

        // 4. Calcular el saldo total pendiente
        $saldoPendiente = array_reduce($pendingItems, function ($sum, $item) {
            return $sum + (float) $item['amount'];
        }, 0.0);

        // 5. Histórico de pagos procesados/pagados paginado a 10 elementos
        $paginatedCharges = FinancialCharge::with(['contractedService.service', 'payments'])
            ->whereIn('residence_id', $residenceIds)
            ->whereIn('status', ['paid', 'approved', 'refunded', 'cancelled'])
            ->orderBy('updated_at', 'desc')
            ->paginate(10);

        $historicalData = $paginatedCharges->through(function ($charge) {
            $cs = $charge->contractedService;
            $latestPayment = $charge->payments->last();
            $title = $cs && $cs->service
                ? $cs->service->title
                : "Mantenimiento Mensual - {$charge->month}/{$charge->year}";

            return [
                'id' => $charge->id,
                'payment_id' => $latestPayment?->id,
                'title' => $title,
                'amount' => number_format($charge->amount, 2, '.', ''),
                'payment_method' => $latestPayment?->payment_method ?? ($cs?->payment_method ?? 'stripe'),
                'receipt_url' => $latestPayment?->receipt ? asset('storage/' . ltrim($latestPayment->receipt, '/')) : null,
                'status' => $charge->status,
                'month' => $charge->month,
                'year' => $charge->year,
                'date' => $charge->updated_at?->toIso8601String(),
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => [
                'saldo_pendiente' => number_format($saldoPendiente, 2, '.', ''),
                'pagos_pendientes' => $pendingItems,
                'historico_pagos' => $historicalData,
            ],
        ]);
    }

    /**
     * Procesar el pago de conceptos de deuda seleccionados por el usuario.
     */
    public function processPayment(Request $request)
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.type' => 'required|string|in:financial_charge,contracted_service,monthly_fee',
            'items.*.id' => 'nullable|integer',
            'items.*.residence_id' => 'nullable|exists:residences,id',
            'payment_method' => 'required|string',
            'receipt' => 'nullable',
        ]);

        $user = $request->user();
        $userResidences = $user->residences;
        $primaryResidenceId = $userResidences->first()?->id;

        if (!$primaryResidenceId) {
            return response()->json([
                'status' => 'error',
                'message' => 'El usuario no tiene residencias asociadas.',
            ], 400);
        }

        $receiptPath = null;
        if ($request->hasFile('receipt')) {
            $receiptPath = $request->file('receipt')->store('receipts', 'public');
        } elseif (is_string($request->receipt) && !empty($request->receipt)) {
            $receiptPath = $request->receipt;
        }

        $now = Carbon::now();
        $processedPayments = [];
        $totalPaidAmount = 0.0;

        foreach ($request->items as $item) {
            $type = $item['type'];
            $itemId = $item['id'] ?? null;
            $residenceId = $item['residence_id'] ?? $primaryResidenceId;

            $charge = null;

            if ($type === 'monthly_fee' || ($type === 'financial_charge' && !$itemId)) {
                // Crear o encontrar la cuota mensual del mes actual
                $recurrentServicePrice = Service::where('is_recurrent', true)->where('is_active', true)->sum('price');
                if ($recurrentServicePrice <= 0) {
                    $recurrentServicePrice = 200.00;
                }

                $charge = FinancialCharge::firstOrCreate(
                    [
                        'residence_id' => $residenceId,
                        'month' => $now->month,
                        'year' => $now->year,
                    ],
                    [
                        'amount' => $recurrentServicePrice,
                        'status' => 'pending',
                    ]
                );
            } elseif ($type === 'financial_charge' && $itemId) {
                $charge = FinancialCharge::whereIn('residence_id', $userResidences->pluck('id'))
                    ->find($itemId);
            } elseif ($type === 'contracted_service' && $itemId) {
                $contractedService = ContractedService::where('user_id', $user->id)->find($itemId);
                if ($contractedService) {
                    if ($contractedService->charge_id) {
                        $charge = FinancialCharge::find($contractedService->charge_id);
                    } else {
                        $charge = FinancialCharge::create([
                            'residence_id' => $contractedService->residence_id,
                            'amount' => $contractedService->amount,
                            'month' => $now->month,
                            'year' => $now->year,
                            'status' => 'pending',
                        ]);
                        $contractedService->update(['charge_id' => $charge->id]);
                    }
                }
            }

            if (!$charge) {
                continue;
            }

            // Actualizar estado del cargo financiero a pagado
            $charge->update(['status' => 'paid']);

            // Crear el registro formal de pago
            $payment = Payment::create([
                'charge_id' => $charge->id,
                'user_id' => $user->id,
                'amount' => $charge->amount,
                'payment_method' => $request->payment_method,
                'receipt' => $receiptPath,
                'status' => 'approved',
            ]);

            $totalPaidAmount += (float) $charge->amount;
            $processedPayments[] = [
                'payment_id' => $payment->id,
                'charge_id' => $charge->id,
                'amount' => number_format($charge->amount, 2, '.', ''),
                'receipt_url' => $payment->receipt ? asset('storage/' . ltrim($payment->receipt, '/')) : null,
                'status' => 'approved',
            ];
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Pago procesado exitosamente.',
            'data' => [
                'total_paid' => number_format($totalPaidAmount, 2, '.', ''),
                'payment_method' => $request->payment_method,
                'payments' => $processedPayments,
            ],
        ], 200);
    }
}
