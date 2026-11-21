<?php

namespace App\Http\Controllers\GymAdmin;

use App\Http\Controllers\Controller;
use App\Models\RegistrationRequest;
use App\Services\RegistrationService;
use Illuminate\Http\Request;

/** Join requests sent from the mobile app: the gym admin approves or rejects them. */
class RegistrationRequestController extends Controller
{
    public const TABS = ['pending' => 'Waiting', 'approved' => 'Approved', 'rejected' => 'Rejected'];

    public function index(Request $request)
    {
        $tab = array_key_exists($request->query('status'), self::TABS) ? $request->query('status') : 'pending';
        $gymId = $request->user()->gym_id;

        return view('gym-admin.registrations.index', [
            'tab' => $tab,
            'requests' => RegistrationRequest::with('user', 'decider')->where('gym_id', $gymId)->where('status', $tab)
                ->latest()->paginate(25)->withQueryString(),
            'counts' => RegistrationRequest::where('gym_id', $gymId)->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status'),
        ]);
    }

    public function approve(Request $request, RegistrationRequest $registration, RegistrationService $service)
    {
        $this->authorizeGym($request, $registration);
        $service->approve($registration, $request->user());

        return back()->with('status', "{$registration->user->name} is now a member. They can use the app straight away.");
    }

    public function reject(Request $request, RegistrationRequest $registration, RegistrationService $service)
    {
        $this->authorizeGym($request, $registration);
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:200']]);
        $service->reject($registration, $request->user(), $data['reason'] ?? null);

        return back()->with('status', "{$registration->user->name}'s request was rejected.");
    }

    private function authorizeGym(Request $request, RegistrationRequest $registration): void
    {
        abort_unless((int) $registration->gym_id === (int) $request->user()->gym_id, 403);
    }
}
