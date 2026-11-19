<?php

namespace App\Services;

use App\Models\Membership;
use App\Models\MembershipPlan;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\MembershipActivated;
use App\Notifications\MembershipAwaitingApproval;
use App\Notifications\MembershipRejected;
use App\Support\Roles;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

/** All membership state changes go through here: request → (payment | admin approval) → active. */
class MembershipService
{
    public function request(User $member, MembershipPlan $plan): Membership
    {
        // Replace any earlier unpaid request so a member has at most one pending.
        Membership::where('user_id', $member->id)->where('status', 'pending')->update(['status' => 'cancelled']);

        $membership = Membership::create([
            'user_id' => $member->id,
            'gym_id' => $member->gym_id,
            'membership_plan_id' => $plan->id,
            'status' => 'pending',
            'amount' => $plan->price,
        ]);

        $admins = User::role(Roles::GYM_ADMIN)->where('gym_id', $member->gym_id)->where('status', 'active')->get();
        Notification::send($admins, new MembershipAwaitingApproval($member, $membership->load('plan')));

        return $membership;
    }

    /** Staff confirm a request was paid at the desk: activate it and record the payment. */
    public function approve(Membership $membership, User $reviewer, string $method = 'desk', ?string $note = null): void
    {
        DB::transaction(function () use ($membership, $reviewer, $method, $note) {
            $membership->activate('admin', $reviewer);
            $this->recordDeskPayment($membership, $reviewer, $method, $note);
        });
        $membership->user->notify(new MembershipActivated($membership->fresh('plan')));
    }

    /**
     * Renew or start a membership and collect the fee at the desk in one step.
     * A new period starts the day after the current one ends (Membership::activate).
     */
    public function renew(User $member, MembershipPlan $plan, User $staff, string $method, ?float $amount = null, ?string $note = null): Membership
    {
        $membership = DB::transaction(function () use ($member, $plan, $staff, $method, $amount, $note) {
            Membership::where('user_id', $member->id)->where('status', 'pending')->update(['status' => 'cancelled']);

            $membership = Membership::create([
                'user_id' => $member->id,
                'gym_id' => $member->gym_id,
                'membership_plan_id' => $plan->id,
                'status' => 'pending',
                'amount' => $amount ?? $plan->price,
                'notes' => 'Collected at the desk by ' . $staff->name,
            ]);
            $membership->setRelation('plan', $plan);
            $membership->activate('admin', $staff);
            $this->recordDeskPayment($membership, $staff, $method, $note);

            return $membership;
        });
        $member->notify(new MembershipActivated($membership->fresh('plan')));

        return $membership;
    }

    public function recordDeskPayment(Membership $membership, User $staff, string $method, ?string $note = null): Payment
    {
        return Payment::create([
            'user_id' => $membership->user_id,
            'membership_id' => $membership->id,
            'gateway' => $method,
            'gateway_order_id' => 'desk_' . \Illuminate\Support\Str::uuid(),
            'amount_paise' => (int) round((float) $membership->amount * 100),
            'currency' => 'INR',
            'status' => 'paid',
            'paid_at' => now(),
            'recorded_by' => $staff->id,
            'note' => $note,
        ]);
    }

    public function reject(Membership $membership, User $reviewer, ?string $reason): void
    {
        $membership->update([
            'status' => 'rejected',
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'notes' => $reason,
        ]);
        $membership->user->notify(new MembershipRejected($membership->load('plan')));
    }

    /** Idempotent: safe to call from both the checkout callback and the webhook. */
    public function markPaid(Payment $payment, string $gatewayPaymentId): void
    {
        $activated = DB::transaction(function () use ($payment, $gatewayPaymentId) {
            $payment = Payment::whereKey($payment->id)->lockForUpdate()->first();
            if ($payment->status === 'paid') {
                return null;
            }
            $payment->update(['status' => 'paid', 'gateway_payment_id' => $gatewayPaymentId, 'paid_at' => now()]);

            $membership = $payment->membership()->with('plan')->first();
            if ($membership->status === 'pending') {
                $membership->activate('payment');

                return $membership;
            }

            return null;
        });

        if ($activated) {
            $activated->user->notify(new MembershipActivated($activated->fresh('plan')));
        }
    }
}
