<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Resources\UserResource;
use App\Mail\ResetPasswordLink;
use App\Mail\VerifyEmailOtpMail;
use App\Models\User;
use App\Services\OtpService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

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
            'message' => 'Login successful',
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

        // 1. Create User
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'role' => $request->role ?? 'user', // Default role jika tidak disediakan
            'is_active' => true, // User aktif, tapi mungkin belum verified (tergantung logika Anda)
        ]);

        // 2. Generate Token (PENTING: Ini baris kuncinya)
        // 'auth_token' adalah nama token, bisa diganti bebas
        $token = $user->createToken('auth_token')->plainTextToken;

        // 3. Generate OTP
        $code = $otpService->generateFor($user);

        // 4. Kirim Email
        Mail::to($user->email)->queue(
            new VerifyEmailOtpMail($code)
        );

        // 5. Return Response Lengkap
        return response()->json([
            'message' => 'Registration successful. OTP sent to your email.',
            'user' => new UserResource($user),        // Data user
            'token' => $token,      // Token string (Bearer ...)
        ], 201); // Gunakan code 201 (Created)
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

    public function resendVerification(Request $request, OtpService $otpService)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::where('email', $request->email)->firstOrFail();

        if (! $otpService->canResend($user)) {
            return response()->json([
                'message' => 'Please wait before requesting another code',
            ], 429);
        }

        if ($user->email_verified_at) {
            return response()->json([
                'message' => 'Email already verified',
            ], 400);
        }

        // hapus otp lama
        $otpService->clear($user);

        // generate otp baru
        $code = $otpService->generateFor($user);

        // kirim email
        Mail::to($user->email)->send(new VerifyEmailOtpMail($code));

        return response()->json([
            'message' => 'Verification code resent successfully',
        ]);
    }

    public function sendResetLink(ForgotPasswordRequest $request)
    {
        $email = $request->email;

        // 1. Rate Limiting (Maks 1 request per menit per email)
        $key = 'password-reset:'.$email;
        if (RateLimiter::tooManyAttempts($key, 1)) {
            $seconds = RateLimiter::availableIn($key);

            return response()->json([
                'message' => "Tunggu $seconds detik sebelum mencoba lagi.",
            ], 429);
        }
        RateLimiter::hit($key, 60);

        // 2. Cek apakah user ada (Keamanan: Tetap berikan respons sukses meski email tidak ada)
        $userExists = DB::table('users')->where('email', $email)->exists();

        if ($userExists) {
            // 3. Gunakan Hashing untuk Token (PENTING!)
            // Jika DB bocor, hacker tidak bisa langsung pakai token untuk reset
            $token = Str::random(64);

            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $email],
                [
                    'token' => Hash::make($token), // Hash tokennya
                    'created_at' => Carbon::now(),
                ]
            );

            // 4. Kirim Email
            Mail::to($email)->send(new ResetPasswordLink($token, $email));
        }

        // 5. Selalu return 200 (Agar hacker tidak tahu email mana yang terdaftar)
        return response()->json([
            'message' => 'Jika email terdaftar, kami telah mengirimkan link reset password.',
        ], 200);
    }

    // Method untuk menampilkan halaman (GET)
    public function showResetForm(Request $request)
    {
        // 1. Validasi parameter URL
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
        ]);

        // 2. Cek apakah token valid di database
        $checkToken = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->where('token', $request->token)
            ->first();

        // Jika token tidak ada, return error (atau view expired)
        if (! $checkToken) {
            abort(404, 'Token invalid atau sudah kadaluarsa.');
            // Atau return view('auth.expired');
        }

        // 3. Tampilkan View (Halaman Penjembatan)
        return view('auth.reset_password', [
            'token' => $request->token,
            'email' => $request->email,
        ]);
    }

    public function resetPassword(ResetPasswordRequest $request)
    {
        // 1. Ambil data token dari database berdasarkan email
        $record = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        // 2. Cek apakah record ada
        if (! $record) {
            return response()->json(['message' => 'Permintaan reset tidak valid.'], 400);
        }

        // 3. Cek Expiry (Contoh: Expired setelah 60 menit)
        $expiresAt = Carbon::parse($record->created_at)->addMinutes(60);
        if (Carbon::now()->gt($expiresAt)) {
            // Hapus token yang sudah expired agar tidak memenuhi DB
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();

            return response()->json(['message' => 'Token sudah kadaluwarsa. Silakan request ulang.'], 400);
        }

        // 4. Verifikasi kecocokan Token (karena di DB kita simpan dalam bentuk Hash)
        if (! Hash::check($request->token, $record->token)) {
            return response()->json(['message' => 'Token tidak valid.'], 400);
        }

        // 5. Update Password User
        $user = User::where('email', $request->email)->first();
        $user->update([
            'password' => Hash::make($request->password),
        ]);

        // 6. "Sekali Pakai": Hapus token setelah berhasil digunakan
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return response()->json([
            'message' => 'Password berhasil diubah. Silakan login kembali.',
        ], 200);
    }

    public function me(Request $request)
    {
        return response()->json([
            'user' => new UserResource($request->user()),
        ]);
    }
}
