<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Gym;
use App\Models\SubscriptionPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/** Subscription payments gyms make to FlexFit. Recording a paid payment activates and extends the subscription. */
class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $payments = SubscriptionPayment::with('gym', 'subscription.plan')
            ->when($request->filled('gym'), function ($q) use ($request) { $q->where('gym_id', $request->gym); })
            ->when($request->filled('status'), function ($q) use ($request) { $q->where('status', $request->status); })
            ->latest('paid_at')->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('super-admin.payments.index', [
            'payments' => $payments,
            'gyms' => Gym::orderBy('name')->get(['id', 'name']),
            'paidThisMonth' => (int) SubscriptionPayment::where('status', 'paid')->where('paid_at', '>=', now()->startOfMonth())->sum('amount'),
            'paidAllTime' => (int) SubscriptionPayment::where('status', 'paid')->sum('amount'),
            'pending' => (int) SubscriptionPayment::where('status', 'pending')->sum('amount'),
        ]);
    }

    public function create(Request $request)
    {
        $gyms = Gym::has('subscription')->with('subscription.plan')->orderBy('name')->get();

        return view('super-admin.payments.create', [
            'gyms' => $gyms,
            'selected' => $request->query('gym'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'gym_id' => ['required', 'exists:gyms,id'],
            'amount' => ['required', 'integer', 'min:1', 'max:10000000'],
            'method' => ['required', Rule::in(array_keys(SubscriptionPayment::METHODS))],
            'reference' => ['nullable', 'string', 'max:120'],
            'status' => ['required', Rule::in(array_keys(SubscriptionPayment::STATUSES))],
            'paid_at' => ['required', 'date', 'before_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $gym = Gym::with('subscription')->findOrFail($data['gym_id']);
        $subscription = $gym->subscription;
        abort_unless($subscription, 422, 'This gym has no subscription yet. Start one first.');

        DB::transaction(function () use ($data, $subscription, $request) {
            $payment = new SubscriptionPayment($data + ['gym_subscription_id' => $subscription->id, 'recorded_by' => $request->user()->id]);

            if ($data['status'] === 'paid') {
                // Extend from the later of today and the current end date, by one billing cycle.
                $from = $subscription->status === 'active' && $subscription->ends_on && $subscription->ends_on->isFuture()
                    ? $subscription->ends_on->copy()
                    : Carbon::today();
                $to = $subscription->billing_cycle === 'yearly' ? $from->copy()->addYear() : $from->copy()->addMonth();

                $payment->period_start = $from;
                $payment->period_end = $to;
                $subscription->update(['status' => 'active', 'ends_on' => $to, 'cancelled_at' => null]);
            }

            $payment->save();
        });

        return redirect()->route('super-admin.payments.index')->with('status', 'Payment of ₹' . number_format($data['amount']) . " recorded for {$gym->name}.");
    }
}
