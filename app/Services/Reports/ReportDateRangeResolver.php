<?php

namespace App\Services\Reports;

use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Único punto que traduce el filtro de reporte (hoy/semana/mes/personalizado)
 * en un rango de fechas concreto, para que los tres reportes (dashboard,
 * finanzas, servicios) interpreten "esta semana" de la misma forma.
 */
class ReportDateRangeResolver
{
    public function resolve(Request $request): ReportDateRange
    {
        Carbon::setLocale('es');

        $range = $request->query('range', 'month');
        $now = Carbon::now();

        return match ($range) {
            'today' => new ReportDateRange(
                $now->copy()->startOfDay(),
                $now->copy()->endOfDay(),
                'Hoy · ' . $now->translatedFormat('d \d\e F \d\e Y'),
                'today'
            ),
            'week' => new ReportDateRange(
                $now->copy()->startOfWeek(),
                $now->copy()->endOfWeek(),
                'Esta semana (' . $now->copy()->startOfWeek()->format('d/m') . ' - ' . $now->copy()->endOfWeek()->format('d/m/Y') . ')',
                'week'
            ),
            'custom' => $this->resolveCustom($request, $now),
            default => new ReportDateRange(
                $now->copy()->startOfMonth(),
                $now->copy()->endOfMonth(),
                ucfirst($now->translatedFormat('F \d\e Y')),
                'month'
            ),
        };
    }

    private function resolveCustom(Request $request, Carbon $now): ReportDateRange
    {
        $from = $request->filled('from')
            ? Carbon::parse($request->query('from'))->startOfDay()
            : $now->copy()->startOfMonth();

        $to = $request->filled('to')
            ? Carbon::parse($request->query('to'))->endOfDay()
            : $now->copy()->endOfDay();

        if ($from->greaterThan($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        $label = 'Del ' . $from->format('d/m/Y') . ' al ' . $to->format('d/m/Y');

        return new ReportDateRange($from, $to, $label, 'custom');
    }
}
