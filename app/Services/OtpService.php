<?php
namespace App\Services;

use App\Models\EmailVerificationCode;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class OtpService
{
    public function generateFor($user)
    {
        $code = random_int(100000, 999999);

        EmailVerificationCode::create([
            'user_id' => $user->id,
            'code' => Hash::make($code),
            'expires_at' => now()->addMinutes(10),
        ]);

        return $code;
    }

    public function verify($user, $inputCode)
    {
        $record = EmailVerificationCode::where('user_id', $user->id)
            ->latest()
            ->first();

        if (! $record) {
            return false;
        }

        if ($record->expires_at->isPast()) {
            return false;
        }

        return Hash::check($inputCode, $record->code);
    }

    public function clear($user)
    {
        EmailVerificationCode::where('user_id', $user->id)->delete();
    }
}
