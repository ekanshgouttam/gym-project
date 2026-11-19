<?php

namespace App\Services;

use App\Models\OtpVerification;
use App\Services\Sms\SmsSender;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class OtpService
{
    public const TTL_MINUTES = 10;
    public const MAX_ATTEMPTS = 5;
    public const RESEND_COOLDOWN_SECONDS = 30;

    /** @var SmsSender */
    private $sms;

    public function __construct(SmsSender $sms)
    {
        $this->sms = $sms;
    }

    /** Normalise an Indian mobile number to +91XXXXXXXXXX, or null if invalid. */
    public static function normalizePhone(?string $raw): ?string
    {
        $digits = preg_replace('/\D/', '', (string) $raw);
        if (strlen($digits) === 12 && strpos($digits, '91') === 0) {
            $digits = substr($digits, 2);
        } elseif (strlen($digits) === 11 && $digits[0] === '0') {
            $digits = substr($digits, 1);
        }

        return preg_match('/^[6-9]\d{9}$/', $digits) ? '+91' . $digits : null;
    }

    public function send(string $phone, string $purpose, ?string $ip = null): void
    {
        $recent = OtpVerification::where('phone', $phone)->where('purpose', $purpose)
            ->where('created_at', '>', now()->subSeconds(self::RESEND_COOLDOWN_SECONDS))
            ->exists();

        if ($recent) {
            throw ValidationException::withMessages(['phone' => 'Please wait a few seconds before requesting another code.']);
        }

        $code = (string) random_int(100000, 999999);

        // Invalidate earlier unused codes for this number.
        OtpVerification::where('phone', $phone)->where('purpose', $purpose)->whereNull('verified_at')->delete();

        OtpVerification::create([
            'phone' => $phone,
            'purpose' => $purpose,
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes(self::TTL_MINUTES),
            'ip' => $ip,
        ]);

        $this->sms->sendOtp($phone, $code);
    }

    public function verify(string $phone, string $purpose, string $code): bool
    {
        $otp = OtpVerification::where('phone', $phone)->where('purpose', $purpose)
            ->whereNull('verified_at')->latest('id')->first();

        if (! $otp || $otp->expires_at->isPast() || $otp->attempts >= self::MAX_ATTEMPTS) {
            return false;
        }

        $otp->increment('attempts');

        if (! Hash::check($code, $otp->code_hash)) {
            return false;
        }

        $otp->forceFill(['verified_at' => now()])->save();

        return true;
    }
}
