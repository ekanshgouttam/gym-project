<?php

namespace App\Http\Controllers\GymAdmin;

use App\Http\Controllers\Controller;
use App\Models\Enquiry;
use App\Models\MembershipPlan;
use App\Models\User;
use App\Services\OtpService;
use App\Support\Roles;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/** Enquiries: log prospects, follow them up, and track how many become members. */
class EnquiryController extends Controller
{
    public function index(Request $request)
    {
        $gymId = $request->user()->gym_id;
        $view = $request->query('view', 'open');
        $base = Enquiry::where('gym_id', $gymId);

        $enquiries = (clone $base)->with('assignee:id,name')
            ->when($view === 'open', function ($q) { $q->open(); })
            ->when($view === 'due', function ($q) { $q->open()->whereDate('next_follow_up_on', '<=', today()); })
            ->when(isset(Enquiry::STATUSES[$view]), function ($q) use ($view) { $q->where('status', $view); })
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%' . $request->q . '%';
                $q->where(function ($w) use ($term) { $w->where('name', 'like', $term)->orWhere('phone', 'like', $term)->orWhere('email', 'like', $term); });
            })
            ->orderByRaw('next_follow_up_on IS NULL, next_follow_up_on')
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $since = now()->subDays(90);
        $recent = (clone $base)->where('created_at', '>=', $since);
        $total90 = (clone $recent)->count();
        $converted90 = (clone $recent)->where('status', 'converted')->count();

        return view('gym-admin.enquiries.index', [
            'enquiries' => $enquiries,
            'view' => $view,
            'counts' => (clone $base)->groupBy('status')->selectRaw('status, COUNT(*) as total')->pluck('total', 'status'),
            'due' => (clone $base)->open()->whereDate('next_follow_up_on', '<=', today())->count(),
            'stats' => [
                'total90' => $total90,
                'converted90' => $converted90,
                'rate' => $total90 ? (int) round($converted90 / $total90 * 100) : 0,
                'avgDays' => (int) round((float) (clone $base)->where('status', 'converted')->whereNotNull('converted_at')
                    ->selectRaw('AVG(DATEDIFF(converted_at, created_at)) as d')->value('d')),
            ],
            'bySource' => (clone $recent)->groupBy('source')
                ->selectRaw("source, COUNT(*) as total, SUM(status = 'converted') as converted")
                ->orderByDesc('total')->get(),
            'plans' => MembershipPlan::availableAt($gymId)->get(),
            'staff' => $this->staff($gymId),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $enquiry = Enquiry::create($data + ['gym_id' => $request->user()->gym_id, 'status' => 'new']);
        $enquiry->log($request->user(), 'Enquiry added (' . Enquiry::SOURCES[$enquiry->source] . ')');

        return redirect()->route('gym-admin.enquiries.show', $enquiry)->with('status', "Enquiry from {$enquiry->name} saved.");
    }

    public function show(Request $request, Enquiry $enquiry)
    {
        $this->authorizeGym($request, $enquiry);

        return view('gym-admin.enquiries.show', [
            'enquiry' => $enquiry->load('activities.user:id,name', 'assignee:id,name', 'member:id,name'),
            'plans' => MembershipPlan::availableAt($enquiry->gym_id)->get(),
            'staff' => $this->staff($enquiry->gym_id),
        ]);
    }

    public function update(Request $request, Enquiry $enquiry)
    {
        $this->authorizeGym($request, $enquiry);
        $data = $this->validated($request, true);
        $before = $enquiry->status;

        if ($data['status'] === 'converted' && ! $enquiry->converted_user_id) {
            throw ValidationException::withMessages(['status' => 'Use “Register as member” to convert an enquiry, so the member account is linked.']);
        }
        if ($data['status'] !== 'lost') {
            $data['lost_reason'] = null;
        }

        $enquiry->update($data);
        if ($before !== $enquiry->status) {
            $enquiry->log($request->user(), 'Status: ' . Enquiry::STATUSES[$before] . ' → ' . Enquiry::STATUSES[$enquiry->status] . ($enquiry->lost_reason ? " ({$enquiry->lost_reason})" : ''));
        }
        if ($request->filled('note')) {
            $enquiry->log($request->user(), $request->input('note'));
        }

        return back()->with('status', 'Enquiry updated.');
    }

    private function validated(Request $request, bool $updating = false): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'source' => ['required', Rule::in(array_keys(Enquiry::SOURCES))],
            'interest' => ['nullable', 'string', 'max:120'],
            'next_follow_up_on' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'assigned_to' => ['nullable', Rule::in($this->staff($request->user()->gym_id)->pluck('id')->all())],
            'status' => [$updating ? 'required' : 'nullable', Rule::in(array_keys(Enquiry::STATUSES))],
            'lost_reason' => ['nullable', 'string', 'max:120'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $phone = OtpService::normalizePhone($data['phone']);
        if (! $phone) {
            throw ValidationException::withMessages(['phone' => 'Enter a valid 10-digit Indian mobile number.']);
        }
        $data['phone'] = $phone;
        unset($data['note']);
        if (! $updating) {
            unset($data['status'], $data['lost_reason']);
        }

        return $data;
    }

    private function staff(int $gymId)
    {
        return User::role([Roles::GYM_ADMIN, Roles::TRAINER])->where('gym_id', $gymId)->where('status', 'active')->orderBy('name')->get(['id', 'name']);
    }

    private function authorizeGym(Request $request, Enquiry $enquiry): void
    {
        abort_unless($enquiry->gym_id === $request->user()->gym_id, 404);
    }
}
