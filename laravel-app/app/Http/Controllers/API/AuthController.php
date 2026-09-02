<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Models\User;

class AuthController extends Controller
{
    public function signup(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:4|confirmed',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password
        ]);

        return response()->json([
            'message' => 'Registered successfully',
            'status' => 201,
            'data' => $user,
        ]);
    }

    public function signin(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email|exists:users,email',
            'password' => 'required|string|min:4',
        ]);

        $user = User::where('email', $request->email)->first();
        if(!Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'password' => 'Invalid password',
            ]);
        }
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Signed in successfully',
            'status' => 200,
            'data' => $user,
            'token' => $token,
        ]);
    }

    public function signout(Request $request)
    {
        $user = $request->user();
        $currentToken = $user->currentAccessToken();
        $user->tokens()->where('id', $currentToken->id)->delete();

        return response()->json([
            'message' => 'Signed out successfully',
            'status' => 200,
        ]);
    }

    public function verify(Request $request)
    {
        return response([
            'message' => 'Token is valid.',
            'user' => $request->user()
        ], 200);
    }
}
