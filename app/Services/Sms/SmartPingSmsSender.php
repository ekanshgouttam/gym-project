<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * SmartPing (pgapi.smartping.io) driver. The message must match the DLT template registered
 * for the sender ID word for word, so it lives in config with a :code placeholder.
 */
class SmartPingSmsSender implements SmsSender
{
    public function sendOtp(string $phone, string $code): void
    {
        $response = Http::timeout(15)->get('https://pgapi.smartping.io/fe/api/v1/send', [
            'username' => config('services.sms.smartping_username'),
            'password' => config('services.sms.smartping_password'),
            'unicode' => 'false',
            'from' => config('services.sms.smartping_sender'),
            'to' => ltrim($phone, '+'),
            'text' => str_replace(':code', $code, config('services.sms.smartping_message')),
        ]);

        if ($response->json('statusCode') !== 200 || $response->json('state') !== 'SUBMIT_ACCEPTED') {
            throw new RuntimeException('SMS delivery failed: ' . $response->body());
        }
    }
}
