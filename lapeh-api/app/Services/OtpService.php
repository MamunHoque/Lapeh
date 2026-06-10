<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class OtpService
{
    /**
     * Generate, store (hashed) and "send" an OTP for the user.
     * Returns the plain code so callers can expose it in non-production envs.
     */
    public function generate(User $user): string
    {
        $length = (int) config('lapeh.otp.length', 6);
        $code = str_pad((string) random_int(0, (10 ** $length) - 1), $length, '0', STR_PAD_LEFT);

        $user->forceFill([
            'phone_otp_hash' => Hash::make($code),
            'phone_otp_expires_at' => now()->addMinutes((int) config('lapeh.otp.ttl_minutes', 10)),
        ])->save();

        // No SMS provider yet — log it so it can be read during development.
        Log::info("OTP for {$user->phone}: {$code}");

        return $code;
    }

    /**
     * Verify a code. Accepts the master OTP in non-production envs.
     * On success, marks the phone verified and clears the stored OTP.
     */
    public function verify(User $user, string $code): bool
    {
        if ($this->isDevEnv() && $code === (string) config('lapeh.otp.master')) {
            $this->markVerified($user);
            return true;
        }

        if (! $user->phone_otp_hash || ! $user->phone_otp_expires_at) {
            return false;
        }
        if (now()->greaterThan($user->phone_otp_expires_at)) {
            return false;
        }
        if (! Hash::check($code, $user->phone_otp_hash)) {
            return false;
        }

        $this->markVerified($user);
        return true;
    }

    /** Whether OTP codes may be exposed / master OTP accepted. */
    public function isDevEnv(): bool
    {
        return in_array(app()->environment(), config('lapeh.otp.dev_envs', []), true);
    }

    private function markVerified(User $user): void
    {
        $user->forceFill([
            'phone_verified_at' => now(),
            'phone_otp_hash' => null,
            'phone_otp_expires_at' => null,
            'status' => 'active',
        ])->save();

        // Activate the sender profile once the phone is verified.
        $user->sender?->update(['status' => 'active']);
    }
}
