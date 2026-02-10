<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\EmailVerificationOtp;

class AuthController extends Controller
{
    public function login(LoginRequest $request)
    {
        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Invalid credentials',
            ], 401);
        }

        $user->tokens()->delete();
        $token = $user->createToken('mobile')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => new UserResource($user),
        ]);
    }

    public function logout(Request $request)
    {
        $token = $request->user()->currentAccessToken();

        if ($token) {
            $token->delete();
        }

        return response()->json([
            'message' => 'Logged out successfully',
        ]);
    }

    public function register(RegisterRequest $request, OtpService $otpService)
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'is_active' => true,
        ]);

        $code = $otpService->generateFor($user);

        // kirim email nanti (step berikut)
        Mail::to($user->email)->queue(
            new EmailVerificationOtp($code)
        );

        return response()->json([
            'message' => 'OTP sent to your email',
        ]);
    }

    public function verifyEmail(Request $request, OtpService $otpService)
    {
        $user = User::where('email', $request->email)->firstOrFail();

        if (! $otpService->verify($user, $request->code)) {
            return response()->json([
                'message' => 'Invalid or expired code',
            ], 400);
        }

        $user->update([
            'email_verified_at' => now(),
        ]);

        $otpService->clear($user);

        return response()->json([
            'message' => 'Email verified successfully',
        ]);
    }

    public function me(Request $request)
    {
        return response()->json([
            'user' => new UserResource($request->user()),
        ]);
    }
}
