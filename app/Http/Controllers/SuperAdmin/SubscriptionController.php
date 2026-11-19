<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Gym;
use App\Models\GymSubscription;
use App\Models\SubscriptionPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/** Gym subscriptions to FlexFit, and the SaaS plans they subscribe to. */
class SubscriptionController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status');

        $subscriptions = GymSubscription::with('gym', 'plan')
            ->when($status && isset(GymSubscription::STATUSES[$status]), function ($q) use ($status) { $q->where('status', $status); })
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('super-admin.subscriptions.index', [
            'subscriptions' => $subscriptions,
            'counts' => GymSubscription::groupBy('status')->selectRaw('status, COUNT(*) as total')->pluck('total', 'status'),
            'status' => $status,
            'plans' => SubscriptionPlan::withCount(['subscriptions' => function ($q) { $q->live(); }])->orderBy('sort_order')->get(),
            'unsubscribed' => Gym::doesntHave('subscription')->orderBy('name')->get(),
        ]);
    }

    public function create(Request $request)
    {
        $gym = Gym::findOrFail($request->query('gym'));

        return view('super-admin.subscriptions.form', [
            'subscription' => new GymSubscription(['gym_id' => $gym->id, 'status' => 'active', 'billing_cycle' => 'monthly', 'starts_on' => today(), 'ends_on' => today()->addMonth()]),
            'gym' => $gym,
            'plans' => SubscriptionPlan::orderBy('sort_order')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $gym = Gym::findOrFail($request->input('gym_id'));
        $subscription = GymSubscription::create(['gym_id' => $gym->id] + $this->validated($request));

        return redirect()->route('super-admin.subscriptions.index')->with('status', "{$gym->name} is now on {$subscription->plan->name}.");
    }

    public function edit(GymSubscription $subscription)
    {
        return view('super-admin.subscriptions.form', [
            'subscription' => $subscription,
            'gym' => $subscription->gym,
            'plans' => SubscriptionPlan::orderBy('sort_order')->get(),
        ]);
    }

    public function update(Request $request, GymSubscription $subscription)
    {
        $data = $this->validated($request);
        $data['cancelled_at'] = $data['status'] === 'cancelled' ? ($subscription->cancelled_at ?: now()) : null;
        $subscription->update($data);

        return redirect()->route('super-admin.subscriptions.index')->with('status', "Subscription for {$subscription->gym->name} was updated.");
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'subscription_plan_id' => ['required', 'exists:subscription_plans,id'],
            'billing_cycle' => ['required', Rule::in(['monthly', 'yearly'])],
            'status' => ['required', Rule::in(array_keys(GymSubscription::STATUSES))],
            'amount' => ['nullable', 'integer', 'min:0', 'max:10000000'],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
        ]);

        if ($data['amount'] === null) {
            $data['amount'] = SubscriptionPlan::find($data['subscription_plan_id'])->priceFor($data['billing_cycle']);
        }

        return $data;
    }

    /* ---------- SaaS plans ---------- */

    public function editPlan(SubscriptionPlan $plan)
    {
        return view('super-admin.subscriptions.plan', ['plan' => $plan]);
    }

    public function createPlan()
    {
        return view('super-admin.subscriptions.plan', ['plan' => new SubscriptionPlan(['is_active' => true])]);
    }

    public function storePlan(Request $request)
    {
        $plan = SubscriptionPlan::create($this->validatedPlan($request));

        return redirect()->route('super-admin.subscriptions.index')->with('status', "Plan {$plan->name} was created.");
    }

    public function updatePlan(Request $request, SubscriptionPlan $plan)
    {
        $plan->update($this->validatedPlan($request, $plan));

        return redirect()->route('super-admin.subscriptions.index')->with('status', "Plan {$plan->name} was updated.");
    }

    private function validatedPlan(Request $request, ?SubscriptionPlan $plan = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:60', Rule::unique('subscription_plans', 'name')->ignore(optional($plan)->id)],
            'price_monthly' => ['required', 'integer', 'min:0', 'max:1000000'],
            'price_yearly' => ['nullable', 'integer', 'min:0', 'max:10000000'],
            'member_limit' => ['nullable', 'integer', 'min:1'],
            'ai_scans_per_month' => ['nullable', 'integer', 'min:0'],
            'features' => ['nullable', 'string', 'max:2000'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:1000'],
        ]);

        $data['features'] = collect(preg_split('/\r?\n/', (string) ($data['features'] ?? '')))->map('trim')->filter()->values()->all();
        $data['is_active'] = $request->boolean('is_active');
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        if (! $plan) {
            $data['slug'] = Str::slug($data['name']);
        }

        return $data;
    }
}
