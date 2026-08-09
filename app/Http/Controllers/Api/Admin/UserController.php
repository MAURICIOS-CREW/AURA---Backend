<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function residents()
    {
        $residents = User::query()
            ->whereHas('role', function ($query) {
                $query->where('name', 'resident');
            })
            ->select([
                'id',
                'name',
                'username',
                'email',
                'phone',
                'is_active',
            ])
            ->orderBy('name')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $residents,
        ]);
    }

    /**
     * Residencias de un residente en particular, para poblar el selector del modal de
     * "Asignar servicio" una vez que se elige a quién se le asigna.
     */
    public function residences(User $user)
    {
        abort_unless($user->role && $user->role->name === 'resident', 404, 'El usuario indicado no es un residente.');

        $residences = $user->residences()->with('address')->get();

        return response()->json([
            'status' => 'success',
            'data' => $residences,
        ]);
    }
}