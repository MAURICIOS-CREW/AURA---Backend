<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\ContractedService;
use App\Models\FinancialCharge;
use App\Models\Payment;
use App\Models\Service;
use App\Services\StripeService;
use Carbon\Carbon;
use Illuminate\Http\Request;

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
                'receipt_url' => $latestPayment?->receipt_url ?? ($latestPayment?->receipt ? asset('storage/' . ltrim($latestPayment->receipt, '/')) : null),
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
     * Crear PaymentIntent en Stripe para inicializar PaymentSheet en Android.
     */
    public function createIntent(Request $request, StripeService $stripeService)
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.type' => 'required|string|in:financial_charge,contracted_service,monthly_fee',
            'items.*.id' => 'nullable|integer',
            'items.*.residence_id' => 'nullable|exists:residences,id',
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

        $now = Carbon::now();
        $totalAmount = 0.0;
        $chargeIds = [];

        foreach ($request->items as $item) {
            $type = $item['type'];
            $itemId = $item['id'] ?? null;
            $residenceId = $item['residence_id'] ?? $primaryResidenceId;

            if ($type === 'monthly_fee' || ($type === 'financial_charge' && !$itemId)) {
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
                $totalAmount += (float) $charge->amount;
                $chargeIds[] = $charge->id;
            } elseif ($type === 'financial_charge' && $itemId) {
                $charge = FinancialCharge::whereIn('residence_id', $userResidences->pluck('id'))->find($itemId);
                if ($charge) {
                    $totalAmount += (float) $charge->amount;
                    $chargeIds[] = $charge->id;
                }
            } elseif ($type === 'contracted_service' && $itemId) {
                $cs = ContractedService::where('user_id', $user->id)->find($itemId);
                if ($cs) {
                    $totalAmount += (float) $cs->amount;
                    if ($cs->charge_id) {
                        $chargeIds[] = $cs->charge_id;
                    }
                }
            }
        }

        if ($totalAmount <= 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'El monto total a pagar debe ser mayor a 0.',
            ], 400);
        }

        try {
            $intent = $stripeService->createPaymentIntent($totalAmount, [
                'user_id' => (string) $user->id,
                'charge_ids' => implode(',', $chargeIds),
            ]);

            return response()->json([
                'status' => 'success',
                'data' => [
                    'client_secret' => $intent->client_secret,
                    'publishable_key' => $stripeService->getPublishableKey(),
                    'payment_intent_id' => $intent->id,
                    'amount' => number_format($totalAmount, 2, '.', ''),
                    'currency' => config('services.stripe.currency', 'mxn'),
                ],
            ]);
        } catch (\Throwable $e) {
            $error = $stripeService->parseStripeException($e);
            return response()->json([
                'status' => 'error',
                'message' => $error['user_message'],
                'failure_reason' => $error['failure_reason'],
            ], 500);
        }
    }

    /**
     * Procesar el pago de conceptos de deuda seleccionados por el usuario.
     */
    public function processPayment(Request $request, StripeService $stripeService)
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.type' => 'required|string|in:financial_charge,contracted_service,monthly_fee',
            'items.*.id' => 'nullable|integer',
            'items.*.residence_id' => 'nullable|exists:residences,id',
            'payment_method' => 'required|string',
            'stripe_payment_intent_id' => 'nullable|string',
            'payment_method_id' => 'nullable|string',
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
        $chargesToPay = [];
        $totalAmount = 0.0;

        foreach ($request->items as $item) {
            $type = $item['type'];
            $itemId = $item['id'] ?? null;
            $residenceId = $item['residence_id'] ?? $primaryResidenceId;
            $charge = null;

            if ($type === 'monthly_fee' || ($type === 'financial_charge' && !$itemId)) {
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

            if ($charge) {
                $chargesToPay[] = $charge;
                $totalAmount += (float) $charge->amount;
            }
        }

        if (empty($chargesToPay)) {
            return response()->json([
                'status' => 'error',
                'message' => 'No se encontraron cargos válidos para procesar.',
            ], 400);
        }

        // Procesar la transacción mediante el servicio de Stripe
        $txResult = $stripeService->processPaymentTransaction(
            $totalAmount,
            $request->payment_method,
            $request->stripe_payment_intent_id,
            $request->payment_method_id,
            ['user_id' => (string) $user->id]
        );

        $stripeStatus = $txResult['status'];
        $stripePaymentIntentId = $txResult['payment_intent_id'];
        $stripePaymentMethodId = $txResult['payment_method_id'];
        $stripeReceiptUrl = $txResult['receipt_url'];
        $failureCode = $txResult['failure_code'];
        $failureReason = $txResult['failure_reason'];
        $userMessage = $txResult['user_message'];

        $processedPayments = [];
        $totalPaidAmount = 0.0;

        foreach ($chargesToPay as $charge) {
            if ($stripeStatus === 'approved') {
                $charge->update(['status' => 'paid']);
            } elseif ($stripeStatus === 'refused') {
                // Conservar cargo en pendiente en caso de rechazo
                $charge->update(['status' => 'pending']);
            }

            $payment = Payment::create([
                'charge_id' => $charge->id,
                'user_id' => $user->id,
                'amount' => $charge->amount,
                'payment_method' => $request->payment_method,
                'receipt' => $receiptPath,
                'status' => $stripeStatus,
                'stripe_payment_intent_id' => $stripePaymentIntentId,
                'stripe_payment_method_id' => $stripePaymentMethodId,
                'failure_code' => $failureCode,
                'failure_reason' => $failureReason,
                'receipt_url' => $stripeReceiptUrl,
            ]);

            if ($stripeStatus === 'approved') {
                $totalPaidAmount += (float) $charge->amount;
            }

            $processedPayments[] = [
                'payment_id' => $payment->id,
                'charge_id' => $charge->id,
                'amount' => number_format($charge->amount, 2, '.', ''),
                'receipt_url' => $payment->receipt_url ?? ($payment->receipt ? asset('storage/' . ltrim($payment->receipt, '/')) : null),
                'status' => $stripeStatus,
                'failure_code' => $failureCode,
                'failure_reason' => $failureReason,
            ];
        }

        if ($stripeStatus === 'refused') {
            return response()->json([
                'status' => 'error',
                'message' => $userMessage ?? 'El pago fue rechazado por Stripe.',
                'decline_code' => $failureCode,
                'failure_reason' => $failureReason,
                'data' => [
                    'total_paid' => '0.00',
                    'payment_method' => $request->payment_method,
                    'payments' => $processedPayments,
                ],
            ], 400);
        }

        return response()->json([
            'status' => 'success',
            'message' => $stripeStatus === 'pending' ? 'El pago se encuentra en proceso de verificación por Stripe.' : 'Pago procesado exitosamente.',
            'data' => [
                'total_paid' => number_format($totalPaidAmount, 2, '.', ''),
                'payment_method' => $request->payment_method,
                'payments' => $processedPayments,
            ],
        ], 200);
    }
}

