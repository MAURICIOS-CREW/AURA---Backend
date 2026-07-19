<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Incident;
use App\Models\IncidentComment;
use Illuminate\Http\Request;

class IncidentCommentController extends Controller {
    public function index(Incident $incident){
        $comments = $incident->comments()->with('user')->get();
        return response()->json($comments);
    }

    public function store(Request $request, Incident $incident){
        $validated = $request->validate([
            'content' => 'required|string',
        ]);

        $comment = $incident->comments()->create([
            'user_id' => $request->user()->id,
            'content' => $validated['content'],
        ]);

        return response()->json($comment->load('user'), 201);
    }

    public function destroy(Incident $incident, IncidentComment $comment){
        if ($comment->incident_id !== $incident->id) {
            return response()->json(['message' => 'Comment does not belong to this incident'], 400);
        }

        $comment->delete();

        return response()->json(null, 204);
    }
}
