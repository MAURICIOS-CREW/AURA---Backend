<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Incident;
use Illuminate\Http\Request;

class IncidentController extends Controller
{
    public function index(Request $request){
        $incidents = Incident::where('reporter_user_id', $request->user()->id)->get();
        return response()->json($incidents);
    }

    public function store(Request $request){
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $incident = Incident::create([
            'reporter_user_id' => $request->user()->id,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'status' => 'open',
        ]);

        return response()->json($incident, 201);
    }

    public function show(Request $request, Incident $incident){
        if ($incident->reporter_user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json($incident->load(['comments.user']));
    }

    public function update(Request $request, Incident $incident){
        if ($incident->reporter_user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'status' => 'required|in:cancelled',
        ]);

        // Solo permitir cancelar si está open o viewed
        if (!in_array($incident->status, ['open', 'viewed'])) {
            return response()->json(['message' => 'Cannot cancel this incident'], 400);
        }

        $incident->update(['status' => $validated['status']]);

        return response()->json($incident);
    }

    public function destroy(Incident $incident){
        // Generalmente no se eliminan, se cancelan. Pero si quisieran eliminar:
        return response()->json(['message' => 'Method not allowed. Cancel it instead.'], 405);
    }
}
