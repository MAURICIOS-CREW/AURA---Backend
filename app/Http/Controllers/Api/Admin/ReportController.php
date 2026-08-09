<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportController extends Controller
{
    public function dashboard()
    {
        $data = [
            'title' => 'Reporte del Dashboard AURA',
            'generated_at' => now(),
        ];

        return Pdf::loadView(
            'reports.dashboard',
            $data
        )->download('reporte-dashboard-aura.pdf');
    }
}
