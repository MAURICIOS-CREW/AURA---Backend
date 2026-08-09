<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccessCode;
use App\Models\Incident;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use App\Models\User;

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

public function store(Request $request)
{
    $validated = $request->validate([
        'reporter_user_id' => 'required|integer|exists:users,id',
        'title' => 'required|string|max:255',
        'description' => 'required|string',
        'status' => 'required|in:open,viewed,in_progress,attended',
    ]);

    $reporter = User::where('id', $validated['reporter_user_id'])
        ->whereHas('role', function ($query) {
            $query->where('name', 'resident');
        })
        ->first();

    if (!$reporter) {
        return response()->json([
            'message' => 'El usuario seleccionado no es un residente válido.',
        ], 422);
    }

    $incident = Incident::create([
        'reporter_user_id' => $reporter->id,
        'title' => $validated['title'],
        'description' => $validated['description'],
        'status' => $validated['status'],
    ]);

    $incident->load([
        'reporter:id,name,username,email,phone',
    ]);

    return response()->json($incident, 201);
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
