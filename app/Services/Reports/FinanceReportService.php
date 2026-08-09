<?php

namespace App\Services\Reports;

use App\Models\Expense;
use App\Models\Payment;
use Carbon\Carbon;

/**
 * Agrega los datos financieros para el reporte en PDF: además de replicar
 * FinanceController::summary() (acotado al rango elegido), añade desgloses
 * y una proyección simple que no están en la vista en vivo pero son útiles
 * para un reporte administrativo.
 */
class FinanceReportService
{
    private const PREDICTION_MONTHS = 6;

    public function build(ReportDateRange $range): array
    {
        $incomeRange = Payment::where('status', 'approved')
            ->whereBetween('created_at', [$range->from, $range->to])
            ->sum('amount');

        $expensesRange = Expense::where('status', 'paid')
            ->whereBetween('expense_date', [$range->from, $range->to])
            ->sum('amount');

        $totalIncome = Payment::where('status', 'approved')->sum('amount');
        $totalExpenses = Expense::where('status', 'paid')->sum('amount');

        $movements = $this->buildMovements($range);

        return [
            'income_range' => $incomeRange,
            'expenses_range' => $expensesRange,
            'net_range' => $incomeRange - $expensesRange,
            'total_income' => $totalIncome,
            'total_expenses' => $totalExpenses,
            'current_balance' => $totalIncome - $totalExpenses,
            'movements' => $movements,
            'expense_breakdown' => $this->expenseBreakdown($range),
            'income_breakdown' => $this->incomeBreakdown($range),
            'pending_transfers' => $this->pendingTransfers(),
            'prediction' => $this->buildPrediction(),
        ];
    }

    private function buildMovements(ReportDateRange $range): array
    {
        $payments = Payment::with([
                'user:id,name',
                'financialCharge.contractedService.service:id,title',
            ])
            ->whereBetween('created_at', [$range->from, $range->to])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function (Payment $payment) {
                $serviceTitle = $payment->financialCharge?->contractedService?->service?->title;

                return [
                    'type' => 'income',
                    'concept' => $serviceTitle ?? 'Cuota de mantenimiento',
                    'counterpart' => $payment->user?->name ?? 'Residente',
                    'amount' => $payment->amount,
                    'status' => $payment->status,
                    'payment_method' => $payment->payment_method,
                    'date' => $payment->created_at,
                ];
            });

        $expenses = Expense::with('registeredBy:id,name')
            ->whereBetween('expense_date', [$range->from, $range->to])
            ->orderBy('expense_date', 'desc')
            ->get()
            ->map(function (Expense $expense) {
                return [
                    'type' => 'expense',
                    'concept' => $expense->concept,
                    'counterpart' => $expense->provider ?? ($expense->registeredBy?->name ?? 'Administración'),
                    'amount' => $expense->amount,
                    'status' => $expense->status,
                    'payment_method' => $expense->payment_method,
                    'date' => $expense->expense_date,
                ];
            });

        return $payments->concat($expenses)
            ->sortByDesc('date')
            ->values()
            ->all();
    }

    private function expenseBreakdown(ReportDateRange $range): array
    {
        return Expense::where('status', 'paid')
            ->whereBetween('expense_date', [$range->from, $range->to])
            ->selectRaw('category, SUM(amount) as total, COUNT(*) as count')
            ->groupBy('category')
            ->orderByDesc('total')
            ->get()
            ->toArray();
    }

    private function incomeBreakdown(ReportDateRange $range): array
    {
        return Payment::where('status', 'approved')
            ->whereBetween('created_at', [$range->from, $range->to])
            ->selectRaw('payment_method, SUM(amount) as total, COUNT(*) as count')
            ->groupBy('payment_method')
            ->orderByDesc('total')
            ->get()
            ->toArray();
    }

    private function pendingTransfers()
    {
        return Payment::with(['user:id,name', 'financialCharge.contractedService.service:id,title'])
            ->where('payment_method', 'transfer')
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Promedio de ingresos/egresos de los últimos N meses completos, usado
     * como proyección simple del próximo mes (sin dependencias externas de ML).
     */
    private function buildPrediction(): array
    {
        $series = [];

        for ($i = self::PREDICTION_MONTHS; $i >= 1; $i--) {
            $monthStart = Carbon::now()->subMonths($i)->startOfMonth();
            $monthEnd = $monthStart->copy()->endOfMonth();

            $income = Payment::where('status', 'approved')
                ->whereBetween('created_at', [$monthStart, $monthEnd])
                ->sum('amount');

            $expense = Expense::where('status', 'paid')
                ->whereBetween('expense_date', [$monthStart, $monthEnd])
                ->sum('amount');

            $series[] = [
                'month' => ucfirst($monthStart->translatedFormat('F Y')),
                'income' => (float) $income,
                'expense' => (float) $expense,
            ];
        }

        $count = count($series) ?: 1;
        $avgIncome = array_sum(array_column($series, 'income')) / $count;
        $avgExpense = array_sum(array_column($series, 'expense')) / $count;

        return [
            'series' => $series,
            'avg_monthly_income' => $avgIncome,
            'avg_monthly_expense' => $avgExpense,
            'projected_next_month_income' => $avgIncome,
            'projected_next_month_expense' => $avgExpense,
            'projected_next_month_net' => $avgIncome - $avgExpense,
        ];
    }
}
