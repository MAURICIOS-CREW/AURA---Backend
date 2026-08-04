<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function index(Request $request)
    {
        $services = Service::where('is_active', true)
            ->orderBy('title', 'asc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $services,
        ]);
    }

    public function show(Service $service)
    {
        if (!$service->is_active) {
            return response()->json([
                'status' => 'error',
                'message' => 'Servicio no disponible.',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $service,
        ]);
    }
}
