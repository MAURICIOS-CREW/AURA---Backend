<?php

namespace App\Services\Reports;

use App\Models\ContractedService;

/**
 * Agrega los datos de servicios contratados para el reporte en PDF: mismos
 * eager-loads que ContractedServiceController::index(), más desgloses útiles
 * (ranking de servicios más solicitados, ingresos por servicio, tiempo
 * promedio de finalización) que no están disponibles en la vista en vivo.
 */
class ServicesReportService
{
    public function build(ReportDateRange $range): array
    {
        $contractedServices = ContractedService::with([
                'service:id,title,price',
                'user:id,name',
                'residence.address',
                'financialCharge',
            ])
            ->whereBetween('created_at', [$range->from, $range->to])
            ->orderBy('created_at', 'desc')
            ->get();

        $byStatus = $contractedServices->groupBy('status')->map->count();

        $byService = $contractedServices
            ->groupBy(fn (ContractedService $cs) => $cs->service?->title ?? 'Sin servicio')
            ->map(function ($items, $title) {
                return [
                    'title' => $title,
                    'count' => $items->count(),
                    'revenue' => $items->sum('amount'),
                ];
            })
            ->sortByDesc('count')
            ->values();

        $completed = $contractedServices->where('status', 'completed');

        $avgCompletionHours = $completed->isEmpty()
            ? null
            : $completed->avg(fn (ContractedService $cs) => $cs->created_at->diffInHours($cs->updated_at));

        return [
            'contracted_services' => $contractedServices,
            'total_count' => $contractedServices->count(),
            'total_revenue' => $contractedServices->whereNotIn('status', ['cancelled', 'refunded'])->sum('amount'),
            'by_status' => $byStatus,
            'by_service' => $byService,
            'avg_completion_hours' => $avgCompletionHours,
        ];
    }
}
