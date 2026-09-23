<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\User\SignupRequest;
use App\Http\Requests\User\SigninRequest;
use App\Http\Resources\User\UserResource;
use App\Http\Requests\User\SendVerificationEmailRequest;
use App\Http\Requests\User\SendResetPasswordEmailRequest;
use App\Http\Requests\User\SetNewPasswordRequest;
use App\Http\Requests\User\CreatePasswordRequest;
use App\Http\Requests\User\ChangePasswordRequest;
use App\Http\Requests\User\UpdateProfileImageRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use App\Models\User;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Storage;
use Throwable;
use Str;

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

    public function sendResetPasswordEmail(SendResetPasswordEmailRequest $request)
    {
        $status = Password::sendResetLink(
            ['email' => $request->email],
            function ($user, $token) use ($request) {
                $user->sendPasswordResetNotification($token, $request->callback_url);
            }
        );

        if ($status === Password::RESET_LINK_SENT) {
            return response([
                'message' => 'Password reset link sent to your email'
            ], 200);
        }

        return response([
            'message' => 'Password reset link sent to your email'
        ], 200);
    }

    public function setNewPassword(SetNewPasswordRequest $request)
    {
        $status = Password::reset(
            [
                'token' => $request->token,
                'email' => $request->email,
                'password' => $request->password,
                'password_confirmation' => $request->password_confirmation
            ],
            function ($user, $password) {
                $user->password = $password;
                $user->save();
                $user->tokens()->delete();
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'password' => [__($status)],
            ]);
        }

        return response([
            'message' => 'Password has been reset successfully.'
        ], 200);
    }
    public function createPassword(CreatePasswordRequest $request)
    {
        $user = $request->user();
        if (!empty($user->password)) {
            throw ValidationException::withMessages([
                'new_password' => 'Password is already set.',
            ]);
        }
        $user->password = $request->new_password;
        $user->save();
        $user->tokens()->delete();
        return response([
            'message' => 'Password created successfully.'
        ], 200);
    }

    public function changePassword(ChangePasswordRequest $request)
    {
        $user = $request->user();
        if (!Hash::check($request->current_password, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => 'Current password does not match.',
            ]);
        }
        if ($request->current_password === $request->new_password) {
            throw ValidationException::withMessages([
                'new_password' => 'New password must be different from current password.',
            ]);
        }
        $user->password = $request->new_password;
        $user->save();
        $user->tokens()->delete();
        return response([
            'message' => 'Password changed successfully.'
        ], 200);
    }

    public function updateProfileImage(UpdateProfileImageRequest $request)
    {
        $user = $request->user();
        $oldImage = $user->getRawOriginal('profile_image');
        $oldThumbnail = $user->getRawOriginal('profile_thumbnail');
        $newImageFile = $request->file('profile_image');

        // disk to store images
        $disk = Storage::disk('public');
        // image manager instance for handling image operations
        $imageManage = new ImageManager(new Driver());
        $imagePath = 'profile_images/';
        $thumbnailPath = 'profile_images/thumbnails/';

        try {

            $imageSource = $newImageFile->getRealPath();
            if (!$imageSource) {
                throw ValidationException::withMessages([
                    'profile_image' => 'Invalid profile image uploaded.',
                ]);
            }

            $imageData = $imageManage->decodePath($imageSource);
            if (!$imageData) {
                throw ValidationException::withMessages([
                    'profile_image' => 'Failed to decode profile image.',
                ]);
            }
            // get the file extension and generate a unique filename
            $extension = $newImageFile->guessExtension() ?? $newImageFile->extension();
            $filename = Str::uuid() . '.' . $extension;
            $isLandscapeOrSquare = $imageData->width() >= $imageData->height();

            // encode the image using the determined file extension and store it on the disk
            $encodedImage = $imageData->encodeUsingFileExtension($extension);
            $imagePath = $imagePath . $filename;
            $disk->put($imagePath, (string) $encodedImage);


            $thumbnailSize = 512;
            $thumbnail = $imageData->scaleDown(
                width: $isLandscapeOrSquare ? $thumbnailSize : null,
                height: $isLandscapeOrSquare ? null : $thumbnailSize
            );

            $encodedThumbnail = $thumbnail->encodeUsingFileExtension($extension);
            $thumbnailPath = $thumbnailPath . $filename;
            $disk->put($thumbnailPath, (string) $encodedThumbnail);

            $user->profile_image = $imagePath;
            $user->profile_thumbnail = $thumbnailPath;
            $user->save();

        } catch (Throwable $e) {
            // Save failed - delete the newly stored file so no orphan is left.
            if (!is_null($imagePath) && $disk->exists($imagePath)) {
                $disk->delete($imagePath);
            }
            if (!is_null($thumbnailPath) && $disk->exists($thumbnailPath)) {
                $disk->delete($thumbnailPath);
            }
            throw $e;
        }

        // Save succeeded — safe to delete the old file now.
        if (!is_null($oldImage) && $disk->exists($oldImage)) {
            $disk->delete($oldImage);
        }
        if (!is_null($oldThumbnail) && $disk->exists($oldThumbnail)) {
            $disk->delete($oldThumbnail);
        }

        return response([
            'message' => 'User profile image updated successfully.',
            'profile_image' => $user->profile_image,
            'profile_thumbnail' => $user->profile_thumbnail,
        ], 200);
    }

    public function deleteProfileImage(Request $request)
    {
        $user = $request->user();
        $oldImage = $user->getRawOriginal('profile_image');
        $oldThumbnail = $user->getRawOriginal('profile_thumbnail');

        try {
            $user->profile_image = null;
            $user->profile_thumbnail = null;
            $user->save();
        } catch (Throwable $e) {
            throw $e;
        }

        // Save succeeded — safe to delete the file now.
        $disk = Storage::disk('public');
        if (!is_null($oldImage) && $disk->exists($oldImage)) {
            $disk->delete($oldImage);
        }
        if (!is_null($oldThumbnail) && $disk->exists($oldThumbnail)) {
            $disk->delete($oldThumbnail);
        }

        return response([
            'message' => 'User profile image deleted successfully.',
        ], 200);
    }
}
