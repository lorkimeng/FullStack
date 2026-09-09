<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\User\SignupRequest;
use App\Http\Requests\User\SigninRequest;
use App\Http\Resources\User\UserResource;
use App\Http\Requests\User\SendVerificationEmailRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Models\User;

class AuthController extends Controller
{
    public function signup(SignupRequest $request)
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password
        ]);

        $user->sendEmailVerificationNotification($request->callback_url);

        return response()->json([
            'message' => 'Registered successfully',
            'status' => 201,
            'data' => new UserResource($user)
        ]);
    }

    public function signin(SigninRequest $request)
    {
        $user = User::where('email', $request->email)->first();

        if (!$user->hasVerifiedEmail()) {
            throw ValidationException::withMessages([
                'email' => 'Email is not verified.',
            ]);
        }
        
        if(!Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'password' => 'Invalid password',
            ]);
        }
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Signed in successfully',
            'status' => 200,
            'data' => new UserResource($user),
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
            'user' => new UserResource($request->user())
        ], 200);
    }

    public function verifyEmail(Request $request)
    {
        $user = User::findOrFail($request->route('id'));

        if ($user->hasVerifiedEmail()) {
            throw ValidationException::withMessages([
                'email' => 'Email is already verified.',
            ]);
        }

        $user->markEmailAsVerified();

        return response([
            'message' => 'Email verified successfully.'
        ], 200);
    }

    public function sendVerificationEmail(SendVerificationEmailRequest $request)
    {
        $user = User::where('email', $request->email)->first();

        if ($user->hasVerifiedEmail()) {
            throw ValidationException::withMessages([
                'email' => 'Email is already verified.',
            ]);
        }

        $user->sendEmailVerificationNotification($request->callback_url);

        return response([
            'message' => 'Verification email resent.'
        ], 200);
    }
}
