<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/** Production driver using MSG91's OTP API (DLT-approved template required in India). */
class Msg91SmsSender implements SmsSender
{
    public function sendOtp(string $phone, string $code): void
    {
        $response = Http::timeout(15)
            ->withHeaders(['authkey' => config('services.sms.msg91_auth_key')])
            ->post('https://control.msg91.com/api/v5/otp', [
                'template_id' => config('services.sms.msg91_template_id'),
                'mobile' => ltrim($phone, '+'),
                'otp' => $code,
            ]);

        if (! $response->successful() || $response->json('type') === 'error') {
            throw new RuntimeException('SMS delivery failed: ' . $response->body());
        }
    }
}
