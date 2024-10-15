<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    public function getDataUser(Request $request)
    {
        $user = $request->user();

        // Ambil hanya data yang dibutuhkan
        $filteredUserData = [
            'name' => $user->name,
            'email' => $user->email,
            'no_telepon' => $user->no_telepon,
        ];

        return response()->json([
            'status' => 'success',
            'data' => $filteredUserData
        ]);
    }
}
