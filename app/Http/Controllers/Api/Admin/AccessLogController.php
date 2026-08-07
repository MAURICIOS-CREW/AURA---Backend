<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccessLog;
use Illuminate\Http\Request;

class AccessLogController extends Controller
{
    public function index(Request $request)
    {
        $query = AccessLog::select([
            'id',
            'access_type',
            'method',
            'status',
            'message',
            'timestamp',
            'vehicle_id',
            'residence_id',
            'access_code_id'
        ])
        ->with([
            'accessCode:id,guest_name',
            'residence:id,block',
            'vehicle:id,plate,brand,color'
        ]);

        if ($request->filled('category')) {

            switch ($request->category) {

                case 'vehicle':
                    $query->whereNotNull('vehicle_id');
                    break;

                case 'pedestrian':
                    $query->whereNull('vehicle_id');
                    break;
            }
        }

        $accessLogs = $query
            ->latest('timestamp')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $accessLogs
        ]);
    }
}