<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Membership;
use App\Models\MembershipPlan;
use App\Models\Payment;
use App\Services\MembershipService;
use App\Services\RazorpayGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use RuntimeException;

class MembershipController extends Controller
{
    public function show(Request $request, RazorpayGateway $razorpay)
    {
        $user = $request->user();

        return view('member.membership', [
            'active' => $user->activeMembership(),
            'pending' => $user->memberships()->with('plan')->where('status', 'pending')->latest()->first(),
            'history' => $user->memberships()->with('plan', 'payments')->whereIn('status', ['active', 'expired', 'rejected'])->latest()->get(),
            'plans' => MembershipPlan::availableAt($user->gym_id)->get(),
            'paymentsEnabled' => $razorpay->enabled(),
        ]);
    }

    /** Choose / renew a plan → creates a pending membership. */
    public function store(Request $request, MembershipService $memberships)
    {
        $user = $request->user();
        $data = $request->validate([
            'membership_plan_id' => ['required', Rule::in(MembershipPlan::availableAt($user->gym_id)->pluck('id')->all())],
        ]);

        $memberships->request($user, MembershipPlan::findOrFail($data['membership_plan_id']));

        return redirect()->route('member.membership')->with('status', 'Plan selected. Pay online to activate instantly, or the front desk can approve it.');
    }

    /** Create a Razorpay order for a pending membership; returns checkout options. */
    public function checkout(Request $request, Membership $membership, RazorpayGateway $razorpay)
    {
        $user = $request->user();
        abort_unless($membership->user_id === $user->id && $membership->status === 'pending', 404);
        abort_unless($razorpay->enabled(), 503, 'Online payments are not available right now.');

        $amountPaise = (int) round(((float) $membership->amount) * 100);

        try {
            $order = $razorpay->createOrder($amountPaise, 'mship_' . $membership->id . '_' . time(), [
                'membership_id' => (string) $membership->id,
                'member_code' => optional($user->memberProfile)->member_code,
            ]);
        } catch (RuntimeException $e) {
            Log::error($e->getMessage());

            return response()->json(['message' => 'Could not start the payment. Please try again.'], 502);
        }

        Payment::create([
            'user_id' => $user->id,
            'membership_id' => $membership->id,
            'gateway_order_id' => $order['id'],
            'amount_paise' => $amountPaise,
        ]);

        return response()->json([
            'key' => $razorpay->keyId(),
            'order_id' => $order['id'],
            'amount' => $amountPaise,
            'currency' => 'INR',
            'name' => config('app.name'),
            'description' => $membership->plan->name . ' membership',
            'prefill' => ['name' => $user->name, 'email' => $user->email, 'contact' => $user->phone],
        ]);
    }

    /** Razorpay Checkout success handler — verifies the signature before activating. */
    public function verify(Request $request, RazorpayGateway $razorpay, MembershipService $memberships)
    {
        $data = $request->validate([
            'razorpay_order_id' => ['required', 'string'],
            'razorpay_payment_id' => ['required', 'string'],
            'razorpay_signature' => ['required', 'string'],
        ]);

        $payment = Payment::where('gateway_order_id', $data['razorpay_order_id'])
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        if (! $razorpay->verifyPaymentSignature($data['razorpay_order_id'], $data['razorpay_payment_id'], $data['razorpay_signature'])) {
            $payment->update(['status' => 'failed']);

            return response()->json(['message' => 'Payment could not be verified. If money was deducted, it will be refunded automatically.'], 422);
        }

        $memberships->markPaid($payment, $data['razorpay_payment_id']);
        session()->flash('status', 'Payment received — your membership is now active!');

        return response()->json(['redirect' => route('member.membership')]);
    }
}
