<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Incident;
use App\Models\IncidentComment;
use Illuminate\Http\Request;

class IncidentCommentController extends Controller {
    public function index(Request $request, Incident $incident){
        if ($incident->reporter_user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $comments = $incident->comments()->with('user')->get();
        return response()->json($comments);
    }

    public function store(Request $request, Incident $incident) {
        if ($incident->reporter_user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'content' => 'required|string',
        ]);

        $comment = $incident->comments()->create([
            'user_id' => $request->user()->id,
            'content' => $validated['content'],
        ]);

        return response()->json($comment->load('user'), 201);
    }
}
