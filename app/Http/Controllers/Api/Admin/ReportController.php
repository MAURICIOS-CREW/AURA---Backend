<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\Reports\DashboardReportService;
use App\Services\Reports\FinanceReportService;
use App\Services\Reports\ReportDateRangeResolver;
use App\Services\Reports\ServicesReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    private const MODULES = ['dashboard', 'finance', 'services'];

    private const TITLES = [
        'dashboard' => 'Reporte del Dashboard AURA',
        'finance' => 'Reporte Financiero AURA',
        'services' => 'Reporte de Servicios AURA',
    ];

    public function __construct(
        private readonly ReportDateRangeResolver $dateRangeResolver,
        private readonly DashboardReportService $dashboardReportService,
        private readonly FinanceReportService $financeReportService,
        private readonly ServicesReportService $servicesReportService,
    ) {
    }

    /**
     * Devuelve el PDF renderizado en el momento (nunca se guarda en disco).
     * El frontend lo pide con el token Bearer normal, lo recibe como blob y
     * solo entonces abre la pestaña nueva -ya con el contenido listo-, en
     * vez de navegar directo y dejar al usuario viendo una pestaña en blanco
     * mientras dompdf renderiza.
     */
    public function view(Request $request, string $module)
    {
        abort_unless(in_array($module, self::MODULES, true), 404);

        $range = $this->dateRangeResolver->resolve($request);

        [$view, $reportData] = match ($module) {
            'dashboard' => ['reports.dashboard', $this->dashboardReportService->build($range)],
            'finance' => ['reports.finance', $this->financeReportService->build($range)],
            'services' => ['reports.services', $this->servicesReportService->build($range)],
        };

        $data = [
            'title' => self::TITLES[$module],
            'generated_at' => now(),
            'range' => $range,
        ] + $reportData;

        $filename = "reporte-{$module}-aura-" . now()->format('Y-m-d-His') . '.pdf';

        return Pdf::loadView($view, $data)->stream($filename);
    }
}
