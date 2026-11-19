<?php

namespace App\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use App\Models\Membership;
use App\Services\MembershipService;
use Illuminate\Http\Request;

/** Gym Admin (own gym) / Super Admin (all gyms): review and activate membership requests. */
class MembershipApprovalController extends Controller
{
    public function index(Request $request)
    {
        $actor = $request->user();
        $status = in_array($request->query('status'), ['pending', 'active', 'rejected', 'cancelled'], true) ? $request->query('status') : 'pending';

        $memberships = Membership::with('user.memberProfile', 'plan', 'gym', 'payments')
            ->when(! $actor->isSuperAdmin(), function ($q) use ($actor) {
                $q->where('gym_id', $actor->gym_id);
            })
            ->where('status', $status)
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('manage.memberships.index', compact('memberships', 'status'));
    }

    public function approve(Request $request, Membership $membership, MembershipService $service)
    {
        $this->authorizeGym($request, $membership);
        $method = $request->validate(['method' => ['nullable', \Illuminate\Validation\Rule::in(array_keys(\App\Models\Payment::DESK_METHODS))]])['method'] ?? 'desk';
        $service->approve($membership->load('plan', 'user'), $request->user(), $method);

        return back()->with('status', "{$membership->user->name}'s membership is now active.");
    }

    public function reject(Request $request, Membership $membership, MembershipService $service)
    {
        $this->authorizeGym($request, $membership);
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:200']]);
        $service->reject($membership->load('plan', 'user'), $request->user(), $data['reason'] ?? null);

        return back()->with('status', "{$membership->user->name}'s request was rejected.");
    }

    private function authorizeGym(Request $request, Membership $membership): void
    {
        $actor = $request->user();
        abort_unless($actor->isSuperAdmin() || $actor->gym_id === $membership->gym_id, 403);
        abort_unless($membership->status === 'pending', 422, 'This request has already been handled.');
    }
}
