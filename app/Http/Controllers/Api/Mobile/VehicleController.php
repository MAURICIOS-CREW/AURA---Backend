<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Requests\VehicleRequest;
use App\Models\Vehicle;

class VehicleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(){
        $user = request()->user();
        $vehicles = Vehicle::whereIn('residence_id', $user->residences->pluck('id'))->get();
        return response()->json($vehicles);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(VehicleRequest $request){
        $user = request()->user();
        
        if (!$user->residences->contains('id', $request->residence_id)) {
            return response()->json(['message' => 'Unauthorized or Residence not found'], 403);
        }

        $vehicle = Vehicle::create($request->validated());
        return response()->json($vehicle, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Vehicle $vehicle){
        $user = request()->user();

        if (!$user->residences->contains('id', $vehicle->residence_id)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json($vehicle);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(VehicleRequest $request, Vehicle $vehicle){
        $user = request()->user();

        if (!$user->residences->contains('id', $vehicle->residence_id)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $vehicle->update($request->validated());
        return response()->json($vehicle);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Vehicle $vehicle){
        $user = request()->user();

        if (!$user->residences->contains('id', $vehicle->residence_id)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $vehicle->delete();
        return response()->json(['message' => 'Vehicle deleted successfully']);
    }
}
