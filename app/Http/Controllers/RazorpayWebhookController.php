<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\MembershipService;
use App\Services\RazorpayGateway;
use Illuminate\Http\Request;

/**
 * Backstop for payments where the browser closed before the checkout callback ran.
 * Configure in Razorpay Dashboard → Webhooks: URL /webhooks/razorpay, event payment.captured.
 */
class RazorpayWebhookController extends Controller
{
    public function __invoke(Request $request, RazorpayGateway $razorpay, MembershipService $memberships)
    {
        if (! $razorpay->verifyWebhookSignature($request->getContent(), (string) $request->header('X-Razorpay-Signature'))) {
            return response()->json(['message' => 'Invalid signature'], 400);
        }

        if ($request->input('event') === 'payment.captured') {
            $entity = $request->input('payload.payment.entity', []);
            $payment = Payment::where('gateway_order_id', $entity['order_id'] ?? null)->first();

            if ($payment && (int) ($entity['amount'] ?? 0) === (int) $payment->amount_paise) {
                $memberships->markPaid($payment, $entity['id']);
            }
        }

        return response()->json(['ok' => true]);
    }
}
