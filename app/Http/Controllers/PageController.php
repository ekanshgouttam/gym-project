<?php

namespace App\Http\Controllers;

use App\Models\BodyScan;
use App\Models\DemoRequest;
use App\Models\Gym;
use App\Models\MembershipPlan;
use App\Models\PlatformSetting;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\QrCodeService;
use App\Support\Roles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;

/** FlexFit marketing website and the public page of each gym. */
class PageController extends Controller
{
    public function home()
    {
        return view('public.home', [
            'stats' => $this->stats(),
            'plans' => $this->saasPlans(),
            'trialDays' => PlatformSetting::get('trial_days'),
            'gyms' => Gym::where('is_active', true)->latest()->take(6)->get(),
        ]);
    }

    public function features()
    {
        return view('public.features');
    }

    public function howItWorks()
    {
        return view('public.how-it-works', ['exampleGym' => Gym::where('is_active', true)->oldest()->first()]);
    }

    public function pricing()
    {
        return view('public.pricing', ['plans' => $this->saasPlans(), 'trialDays' => PlatformSetting::get('trial_days')]);
    }

    public function about()
    {
        return view('public.about', ['stats' => $this->stats()]);
    }

    public function demo()
    {
        return view('public.demo');
    }

    public function storeDemo(Request $request)
    {
        // Honeypot: real visitors never fill this hidden field.
        if ($request->filled('website')) {
            return redirect()->route('demo')->with('status', 'Thanks! We will call you to fix a time.');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'min:10', 'max:20'],
            'gym_name' => ['required', 'string', 'max:160'],
            'city' => ['nullable', 'string', 'max:100'],
            'members' => ['nullable', Rule::in(array_keys(DemoRequest::SIZES))],
            'preferred_date' => ['nullable', 'date', 'after_or_equal:today', 'before:+3 months'],
            'preferred_time' => ['nullable', Rule::in(array_keys(DemoRequest::TIMES))],
            'message' => ['nullable', 'string', 'max:2000'],
        ]);

        DemoRequest::create($data);

        return redirect()->route('demo')->with('status', "Thanks, {$data['name']}! We'll call you within one working day to confirm your demo.");
    }

    /** Public gym directory: find a gym and join it. */
    public function gyms(Request $request)
    {
        $gyms = Gym::where('is_active', true)
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%' . $request->q . '%';
                $q->where(function ($w) use ($term) { $w->where('name', 'like', $term)->orWhere('city', 'like', $term)->orWhere('address', 'like', $term); });
            })
            ->orderBy('name')
            ->get();

        return view('public.gyms', ['gyms' => $gyms, 'cities' => Gym::where('is_active', true)->whereNotNull('city')->distinct()->orderBy('city')->pluck('city')]);
    }

    /** One gym's public page: branding, plans, trainers and its registration QR code. */
    public function gym(Gym $gym, QrCodeService $qr)
    {
        abort_unless($gym->is_active, 404);

        return view('public.gym', [
            'gym' => $gym,
            'plans' => MembershipPlan::availableAt($gym->id)->get(),
            'trainers' => User::role(Roles::TRAINER)->where('gym_id', $gym->id)->where('status', 'active')
                ->whereHas('trainerProfile', function ($q) { $q->where('is_public', true); })
                ->with('trainerProfile', 'gym')->orderBy('name')->get(),
            'members' => User::role(Roles::MEMBER)->where('gym_id', $gym->id)->where('status', 'active')->count(),
            'qrSvg' => $qr->svg($gym->qrUrl()),
        ]);
    }

    private function saasPlans()
    {
        return SubscriptionPlan::where('is_active', true)->orderBy('sort_order')->get();
    }

    private function stats(): array
    {
        return Cache::remember('public.stats', 600, function () {
            return [
                'gyms' => Gym::where('is_active', true)->count(),
                'members' => User::role(Roles::MEMBER)->where('status', 'active')->count(),
                'trainers' => User::role(Roles::TRAINER)->where('status', 'active')->count(),
                'scans' => BodyScan::where('status', 'completed')->count(),
            ];
        });
    }
}
