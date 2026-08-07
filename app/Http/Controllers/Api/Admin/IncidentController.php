<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccessCode;
use App\Models\Incident;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class IncidentController extends Controller
{
    public function index(Request $request)
{
    $incidents = Incident::with([
        'reporter:id,name,username,email,phone',
    ])
    ->orderBy('created_at', 'desc')
    ->get();

    return response()->json([
        'status' => 'success',
        'data' => $incidents,
    ]);
}

    public function show(Incident $incident)
    {

    $incident->load([
        'reporter:id,name,username,email,phone',
    ]);

    return response()->json([
        'status' => 'success',
        'data' => $incident,
    ]);
}

    public function update(Request $request, Incident $incident){

        $validated = $request->validate([
            'status' => 'required|in:open,viewed,attended,cancelled,in_progress',
        ]);

        $incident->update(['status' => $validated['status']]);

        return response()->json($incident);
    }

    public function destroy(Incident $incident){
        $incident->delete();
        return response()->json(null, 204);
    }
}
