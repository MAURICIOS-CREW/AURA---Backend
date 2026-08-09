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
}