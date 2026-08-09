<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\Payment;
use Carbon\Carbon;

class FinanceController extends Controller
{
    /**
     * Resumen financiero: ingresos y egresos del mes, fondo actual
     * y últimos movimientos combinados (ingresos + egresos).
     */
    public function summary()
    {
        $now = Carbon::now();

        $incomeMonth = Payment::where('status', 'approved')
            ->whereMonth('created_at', $now->month)
            ->whereYear('created_at', $now->year)
            ->sum('amount');

        $expensesMonth = Expense::where('status', 'paid')
            ->whereMonth('expense_date', $now->month)
            ->whereYear('expense_date', $now->year)
            ->sum('amount');

        $totalIncome = Payment::where('status', 'approved')->sum('amount');
        $totalExpenses = Expense::where('status', 'paid')->sum('amount');
        $currentBalance = $totalIncome - $totalExpenses;
        $netMonth = $incomeMonth - $expensesMonth;

        $recentPayments = Payment::with([
                'user:id,name',
                'financialCharge.contractedService.service:id,title',
            ])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function (Payment $payment) {
                $serviceTitle = $payment->financialCharge?->contractedService?->service?->title;

                return [
                    'id' => 'income-' . $payment->id,
                    'type' => 'income',
                    'concept' => $serviceTitle ?? 'Cuota de mantenimiento',
                    'counterpart' => $payment->user?->name ?? 'Residente',
                    'amount' => $payment->amount,
                    'status' => $payment->status,
                    'date' => $payment->created_at,
                ];
            });

        $recentExpenses = Expense::with('registeredBy:id,name')
            ->orderBy('expense_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function (Expense $expense) {
                return [
                    'id' => 'expense-' . $expense->id,
                    'type' => 'expense',
                    'concept' => $expense->concept,
                    'counterpart' => $expense->provider ?? ($expense->registeredBy?->name ?? 'Administración'),
                    'amount' => $expense->amount,
                    'status' => $expense->status,
                    'date' => $expense->expense_date,
                ];
            });

        $recentMovements = $recentPayments
            ->concat($recentExpenses)
            ->sortByDesc('date')
            ->values()
            ->take(15);

        return response()->json([
            'status' => 'success',
            'data' => [
                'income_month' => number_format($incomeMonth, 2, '.', ''),
                'expenses_month' => number_format($expensesMonth, 2, '.', ''),
                'net_month' => number_format($netMonth, 2, '.', ''),
                'total_income' => number_format($totalIncome, 2, '.', ''),
                'total_expenses' => number_format($totalExpenses, 2, '.', ''),
                'current_balance' => number_format($currentBalance, 2, '.', ''),
                'recent_movements' => $recentMovements,
            ],
        ]);
    }
}
