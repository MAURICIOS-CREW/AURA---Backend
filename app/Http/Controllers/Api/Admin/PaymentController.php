<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $query = Payment::with([
            'user:id,name,username,email',
            'financialCharge.contractedService.service:id,title',
            'validatorAdmin:id,name',
        ]);

        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $payments = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'status' => 'success',
            'data' => $payments,
        ]);
    }

    public function approve(Request $request, Payment $payment)
    {
        $this->authorizeTransferReview($payment);

        $payment->update([
            'status' => 'approved',
            'validator_admin_id' => $request->user()->id,
        ]);

        if ($payment->financialCharge) {
            $payment->financialCharge->update(['status' => 'paid']);
        }

        $payment->load(['user:id,name,username,email', 'financialCharge.contractedService.service:id,title', 'validatorAdmin:id,name']);

        return response()->json([
            'status' => 'success',
            'message' => 'Transferencia aprobada correctamente.',
            'data' => $payment,
        ]);
    }

    public function reject(Request $request, Payment $payment)
    {
        $this->authorizeTransferReview($payment);

        $payment->update([
            'status' => 'refused',
            'validator_admin_id' => $request->user()->id,
        ]);

        if ($payment->financialCharge) {
            $payment->financialCharge->update(['status' => 'pending']);
        }

        $payment->load(['user:id,name,username,email', 'financialCharge.contractedService.service:id,title', 'validatorAdmin:id,name']);

        return response()->json([
            'status' => 'success',
            'message' => 'Transferencia rechazada.',
            'data' => $payment,
        ]);
    }

    /**
     * Solo transferencias en estado pendiente pueden ser aprobadas/rechazadas por un admin;
     * los pagos con Stripe se resuelven automáticamente y no pasan por este flujo manual.
     */
    private function authorizeTransferReview(Payment $payment): void
    {
        abort_if(
            $payment->payment_method !== 'transfer' || $payment->status !== 'pending',
            422,
            'Solo se pueden revisar transferencias pendientes.'
        );
    }
}
