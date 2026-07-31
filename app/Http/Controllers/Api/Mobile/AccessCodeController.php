<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\AccessCode;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AccessCodeController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        
        $residenceIds = $user->residences()->pluck('residences.id');

        $codes = AccessCode::whereIn('residence_id', $residenceIds)
            ->where('type', 'custom')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $codes
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'residence_id' => 'required|exists:residences,id',
            'guest_name' => 'required|string|max:255',
            'valid_from' => 'nullable|date',
            'valid_until' => 'nullable|date|after_or_equal:valid_from',
            'max_uses' => 'nullable|integer|min:1',
            'active_days' => 'nullable|array',
            'active_days.*' => 'integer|min:1|max:7',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i',
        ]);

        $user = $request->user();
        
        // Ensure user belongs to this residence
        if (!$user->residences()->where('residences.id', $request->residence_id)->exists()) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $randomData = Str::random(40) . $user->id . uniqid('', true) . $request->guest_name;
        $hash = hash('sha256', $randomData);

        $code = AccessCode::create([
            'residence_id' => $request->residence_id,
            'user_id' => $user->id,
            'guest_name' => $request->guest_name,
            'code' => $hash,
            'type' => 'custom',
            'valid_from' => $request->valid_from,
            'valid_until' => $request->valid_until,
            'uses' => 0,
            'max_uses' => $request->max_uses,
            'active_days' => $request->active_days,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'is_active' => true,
        ]);

        return response()->json([
            'status' => 'success',
            'data' => $code
        ], 201);
    }

    public function show(Request $request, AccessCode $accessCode)
    {
        $user = $request->user();
        if (!$user->residences()->where('residences.id', $accessCode->residence_id)->exists()) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'status' => 'success',
            'data' => $accessCode
        ]);
    }

    public function update(Request $request, AccessCode $accessCode)
    {
        $user = $request->user();
        if (!$user->residences()->where('residences.id', $accessCode->residence_id)->exists()) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'guest_name' => 'sometimes|string|max:255',
            'valid_from' => 'nullable|date',
            'valid_until' => 'nullable|date|after_or_equal:valid_from',
            'max_uses' => 'nullable|integer|min:1',
            'active_days' => 'nullable|array',
            'active_days.*' => 'integer|min:1|max:7',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i',
            'is_active' => 'sometimes|boolean',
        ]);

        $accessCode->update($request->only([
            'guest_name', 'valid_from', 'valid_until', 'max_uses',
            'active_days', 'start_time', 'end_time', 'is_active'
        ]));

        return response()->json([
            'status' => 'success',
            'data' => $accessCode
        ]);
    }

    public function destroy(Request $request, AccessCode $accessCode)
    {
        $user = $request->user();
        if (!$user->residences()->where('residences.id', $accessCode->residence_id)->exists()) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $accessCode->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Código de acceso eliminado (inhabilitado).'
        ]);
    }
}
