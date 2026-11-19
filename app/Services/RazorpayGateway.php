<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/** Minimal Razorpay Orders + signature verification (https://razorpay.com/docs/api/orders/). */
class RazorpayGateway
{
    public function enabled(): bool
    {
        return filled(config('services.razorpay.key_id')) && filled(config('services.razorpay.key_secret'));
    }

    public function keyId(): ?string
    {
        return config('services.razorpay.key_id');
    }

    /** @return array Razorpay order (id, amount, currency, ...) */
    public function createOrder(int $amountPaise, string $receipt, array $notes = []): array
    {
        $response = Http::timeout(20)
            ->withBasicAuth(config('services.razorpay.key_id'), config('services.razorpay.key_secret'))
            ->post('https://api.razorpay.com/v1/orders', [
                'amount' => $amountPaise,
                'currency' => 'INR',
                'receipt' => $receipt,
                'notes' => $notes,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Razorpay order creation failed: ' . $response->body());
        }

        return $response->json();
    }

    public function verifyPaymentSignature(string $orderId, string $paymentId, string $signature): bool
    {
        $expected = hash_hmac('sha256', $orderId . '|' . $paymentId, (string) config('services.razorpay.key_secret'));

        return hash_equals($expected, $signature);
    }

    public function verifyWebhookSignature(string $payload, string $signature): bool
    {
        $secret = (string) config('services.razorpay.webhook_secret');

        return $secret !== '' && hash_equals(hash_hmac('sha256', $payload, $secret), $signature);
    }
}
