<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Incident;
use Illuminate\Http\Request;

class IncidentController extends Controller
{
    public function index(){
        $incidents = Incident::with('reporter')->get();
        return response()->json($incidents);
    }

    public function show(Incident $incident){
        return response()->json($incident->load(['reporter', 'comments.user']));
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
