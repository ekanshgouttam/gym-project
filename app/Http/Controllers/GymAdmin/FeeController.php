<?php

namespace App\Http\Controllers\GymAdmin;

use App\Http\Controllers\Controller;
use App\Models\Membership;
use App\Models\MembershipPlan;
use App\Models\Payment;
use App\Models\User;
use App\Services\MembershipService;
use App\Support\Roles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/** Plans & Fees: the gym's membership plans, fees that are due, and collecting a renewal at the desk. */
class FeeController extends Controller
{
    public function index(Request $request)
    {
        $gymId = $request->user()->gym_id;
        $tab = $request->query('tab', 'due');

        $latest = $this->latestMembershipIds($gymId);
        $memberships = Membership::with('user:id,name,phone', 'plan:id,name,price,duration_months')->whereIn('id', $latest);

        $due = [
            'expiring' => (clone $memberships)->where('status', 'active')->whereBetween('ends_on', [today(), today()->addDays(7)])->orderBy('ends_on')->get(),
            'expired' => (clone $memberships)->whereIn('status', ['active', 'expired'])->where('ends_on', '<', today())->where('ends_on', '>=', today()->subDays(60))->orderByDesc('ends_on')->get(),
            'pending' => Membership::with('user:id,name,phone', 'plan:id,name')->where('gym_id', $gymId)->where('status', 'pending')->latest()->get(),
        ];

        return view('gym-admin.fees.index', [
            'tab' => $tab,
            'due' => $due,
            'ownPlans' => MembershipPlan::where('gym_id', $gymId)->withCount(['memberships' => function ($q) { $q->where('status', 'active'); }])->orderBy('sort_order')->orderBy('price')->get(),
            'sharedPlans' => MembershipPlan::whereNull('gym_id')->where('is_active', true)->orderBy('sort_order')->get(),
            'collectedThisMonth' => (int) round(Payment::where('status', 'paid')->where('paid_at', '>=', now()->startOfMonth())
                ->whereHas('membership', function ($q) use ($gymId) { $q->where('gym_id', $gymId); })->sum('amount_paise') / 100),
            'outstanding' => (float) $due['pending']->sum('amount') + (float) $due['expired']->sum(function ($m) { return $m->plan->price; }),
        ]);
    }

    /* ---------- Plans ---------- */

    public function createPlan()
    {
        return view('gym-admin.fees.plan', ['plan' => new MembershipPlan(['is_active' => true, 'duration_months' => 1])]);
    }

    public function storePlan(Request $request)
    {
        $plan = MembershipPlan::create($this->validatedPlan($request) + ['gym_id' => $request->user()->gym_id]);

        return redirect()->route('gym-admin.fees.index', ['tab' => 'plans'])->with('status', "Plan {$plan->name} created.");
    }

    public function editPlan(Request $request, MembershipPlan $plan)
    {
        abort_unless($plan->gym_id === $request->user()->gym_id, 404);

        return view('gym-admin.fees.plan', ['plan' => $plan]);
    }

    public function updatePlan(Request $request, MembershipPlan $plan)
    {
        abort_unless($plan->gym_id === $request->user()->gym_id, 404);
        $plan->update($this->validatedPlan($request, $plan));

        return redirect()->route('gym-admin.fees.index', ['tab' => 'plans'])->with('status', "Plan {$plan->name} updated.");
    }

    private function validatedPlan(Request $request, ?MembershipPlan $plan = null): array
    {
        $gymId = $request->user()->gym_id;
        $data = $request->validate([
            'name' => ['required', 'string', 'max:60', Rule::unique('membership_plans', 'name')->where('gym_id', $gymId)->ignore(optional($plan)->id)],
            'duration_months' => ['required', 'integer', 'min:1', 'max:36'],
            'price' => ['required', 'numeric', 'min:0', 'max:1000000'],
            'description' => ['nullable', 'string', 'max:255'],
            'features' => ['nullable', 'string', 'max:2000'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:1000'],
        ]);
        $data['features'] = collect(preg_split('/\r?\n/', (string) ($data['features'] ?? '')))->map(function ($f) { return trim($f); })->filter()->values()->all();
        $data['is_active'] = $request->boolean('is_active');
        $data['is_featured'] = $request->boolean('is_featured');
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['slug'] = Str::slug($data['name']);

        return $data;
    }

    /* ---------- Renew / collect fee ---------- */

    public function renewForm(Request $request, User $member)
    {
        $this->authorizeMember($request, $member);

        return view('gym-admin.fees.renew', [
            'member' => $member->load('memberProfile'),
            'current' => $member->activeMembership(),
            'last' => $member->memberships()->with('plan')->whereNotNull('ends_on')->orderByDesc('ends_on')->first(),
            'plans' => MembershipPlan::availableAt($member->gym_id)->get(),
        ]);
    }

    public function renew(Request $request, User $member, MembershipService $service)
    {
        $this->authorizeMember($request, $member);
        $data = $request->validate([
            'membership_plan_id' => ['required', Rule::in(MembershipPlan::availableAt($member->gym_id)->pluck('id')->all())],
            'method' => ['required', Rule::in(array_keys(Payment::DESK_METHODS))],
            'amount' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $membership = $service->renew($member, MembershipPlan::findOrFail($data['membership_plan_id']), $request->user(), $data['method'],
            isset($data['amount']) ? (float) $data['amount'] : null, $data['note'] ?? null);

        return redirect()->route('gym-admin.fees.index')->with('status', "Collected ₹" . number_format((float) $membership->amount) . " from {$member->name}. Active {$membership->starts_on->format('d M')} – {$membership->ends_on->format('d M Y')}.");
    }

    /** The most recent membership per member of this gym. */
    private function latestMembershipIds(int $gymId)
    {
        return Membership::where('gym_id', $gymId)->whereIn('status', ['active', 'expired'])
            ->groupBy('user_id')->select(DB::raw('MAX(id) as id'))->pluck('id');
    }

    private function authorizeMember(Request $request, User $member): void
    {
        abort_unless($member->gym_id === $request->user()->gym_id && $member->hasRole(Roles::MEMBER), 404);
    }
}
