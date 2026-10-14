<?php

namespace App\Services;

use App\Models\OtpVerification;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class OtpService
{
    public function normalizePhone(?string $phone): ?string
    {
        return app(PhoneService::class)->normalizePhone($phone);
    }

    public function generate(string $phone, string $purpose, int $length = 6, int $ttlMinutes = 10): OtpVerification
    {
        $normalized = $this->normalizePhone($phone);

        if (! $normalized) {
            throw ValidationException::withMessages(['phone' => 'Please enter a valid phone number.']);
        }

        OtpVerification::query()
            ->where('phone', $normalized)
            ->where('purpose', $purpose)
            ->whereNull('verified_at')
            ->delete();

        $max = (10 ** $length) - 1;
        $code = str_pad((string) random_int(0, $max), $length, '0', STR_PAD_LEFT);

        return OtpVerification::create([
            'phone' => $normalized,
            'code' => $code,
            'purpose' => $purpose,
            'expires_at' => now()->addMinutes($ttlMinutes),
        ]);
    }

    public function send(OtpVerification $otp): void
    {
        $message = sprintf(
            '[Eternity Gadget OTP] Your verification code for %s is %s. Valid until %s.',
            $otp->purpose,
            $otp->code,
            $otp->expires_at->format('H:i')
        );

        Log::channel('sms')->info($message, [
            'phone' => $otp->phone,
            'purpose' => $otp->purpose,
            'code' => $otp->code,
        ]);
    }

    public function verify(string $phone, string $code, string $purpose): OtpVerification
    {
        $normalized = $this->normalizePhone($phone);

        if (! $normalized) {
            throw ValidationException::withMessages(['phone' => 'Please enter a valid phone number.']);
        }

        $otp = OtpVerification::query()
            ->where('phone', $normalized)
            ->where('purpose', $purpose)
            ->whereNull('verified_at')
            ->latest()
            ->first();

        if (! $otp) {
            throw ValidationException::withMessages(['code' => 'No verification code found for this phone number.']);
        }

        if ($otp->isExpired()) {
            throw ValidationException::withMessages(['code' => 'This verification code has expired.']);
        }

        if (! hash_equals($otp->code, trim($code))) {
            throw ValidationException::withMessages(['code' => 'The verification code is incorrect.']);
        }

        $otp->update(['verified_at' => now()]);

        return $otp->fresh();
    }
}
