<?php

namespace App\Services\Reports;

use App\Models\AccessLog;
use App\Models\ContractedService;
use App\Models\Incident;
use App\Models\User;

/**
 * Agrega los mismos datos que consume el Dashboard (Client/src/app/dashboard),
 * ahora acotados a un rango de fechas, para que el PDF y la vista en vivo
 * cuenten la misma historia.
 */
class DashboardReportService
{
    public function build(ReportDateRange $range): array
    {
        $residents = User::query()
            ->whereHas('role', fn ($q) => $q->where('name', 'resident'))
            ->count();

        $incidentsInRange = Incident::whereBetween('created_at', [$range->from, $range->to])->get();

        $incidentStatusStats = [
            'open' => $incidentsInRange->where('status', 'open')->count(),
            'viewed' => $incidentsInRange->where('status', 'viewed')->count(),
            'in_progress' => $incidentsInRange->where('status', 'in_progress')->count(),
            'attended' => $incidentsInRange->where('status', 'attended')->count(),
        ];

        $pendingServices = ContractedService::with(['service:id,title', 'user:id,name'])
            ->whereIn('status', ['created', 'in_progress'])
            ->orderBy('created_at', 'desc')
            ->get();

        $completedInRange = ContractedService::with(['service:id,title', 'user:id,name'])
            ->where('status', 'completed')
            ->whereBetween('updated_at', [$range->from, $range->to])
            ->orderBy('updated_at', 'desc')
            ->get();

        $accessLogs = AccessLog::with(['residence.address', 'vehicle'])
            ->whereBetween('timestamp', [$range->from, $range->to])
            ->orderBy('timestamp', 'desc')
            ->limit(300)
            ->get();

        $accessTypeBreakdown = [
            'qr' => $accessLogs->where('access_type', 'qr')->count(),
            'plate' => $accessLogs->where('access_type', 'plate')->count(),
        ];

        return [
            'residents_count' => $residents,
            'incident_status_stats' => $incidentStatusStats,
            'incidents_total' => $incidentsInRange->count(),
            'pending_services' => $pendingServices,
            'completed_services' => $completedInRange,
            'access_logs' => $accessLogs,
            'access_logs_total' => $accessLogs->count(),
            'access_type_breakdown' => $accessTypeBreakdown,
        ];
    }
}
