<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $r)
    {
        $data = $r->validate(['email' => 'required|email', 'password' => 'required|string']);
        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            return response()->json(['message' => 'Email atau kata sandi salah.'], 422);
        }

        return [
            'token' => $user->createToken('mobile')->plainTextToken,
            'user' => $user->only('id', 'name', 'email', 'role'),
        ];
    }

    public function logout(Request $r)
    {
        $r->user()->currentAccessToken()->delete();

        return response()->noContent();
    }
}
