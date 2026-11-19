<?php

namespace App\Http\Controllers;

use App\Models\Gym;
use App\Models\JoinVisit;
use App\Models\MemberProfile;
use App\Models\MembershipPlan;
use App\Models\User;
use App\Notifications\WelcomeMember;
use App\Services\BodyScanService;
use App\Services\ImageStore;
use App\Services\MembershipService;
use App\Services\OtpService;
use App\Support\Roles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

/**
 * QR-code registration. Each branch's QR encodes /join/{join_token}.
 * Flow: verify mobile with OTP → fill profile + choose plan → account, Member ID and
 * a pending membership are created → member pays online or waits for admin approval.
 */
class JoinController extends Controller
{
    private const OTP_PURPOSE = 'join';
    private const VERIFIED_SESSION_KEY = 'join.verified_phone';
    private const VERIFIED_TTL_MINUTES = 30;
    private const SOURCE_SESSION_KEY = 'join.source.';

    public function show(Request $request, string $token)
    {
        $gym = $this->gym($token);
        $this->trackVisit($request, $gym);

        return view('join.register', [
            'gym' => $gym,
            'plans' => MembershipPlan::availableAt($gym->id)->get(),
            'goals' => MemberProfile::GOALS,
            'verifiedPhone' => $this->verifiedPhone($gym),
        ]);
    }

    public function sendOtp(Request $request, string $token, OtpService $otp)
    {
        $gym = $this->gym($token);
        $phone = $this->phoneFrom($request);

        if (User::where('phone', $phone)->exists()) {
            throw ValidationException::withMessages(['phone' => 'This mobile number is already registered. Please sign in instead.']);
        }

        $otp->send($phone, self::OTP_PURPOSE . ':' . $gym->id, $request->ip());

        return response()->json([
            'message' => 'We sent a 6-digit code to ' . $this->mask($phone) . '.',
            'resend_after' => OtpService::RESEND_COOLDOWN_SECONDS,
        ]);
    }

    public function verifyOtp(Request $request, string $token, OtpService $otp)
    {
        $gym = $this->gym($token);
        $phone = $this->phoneFrom($request);
        $request->validate(['code' => ['required', 'digits:6']]);

        if (! $otp->verify($phone, self::OTP_PURPOSE . ':' . $gym->id, $request->input('code'))) {
            throw ValidationException::withMessages(['code' => 'That code is incorrect or has expired.']);
        }

        $request->session()->put(self::VERIFIED_SESSION_KEY, [
            'phone' => $phone,
            'gym_id' => $gym->id,
            'at' => now()->timestamp,
        ]);

        return response()->json(['message' => 'Mobile number verified.', 'phone' => $phone]);
    }

    public function store(Request $request, string $token, ImageStore $images, MembershipService $memberships, BodyScanService $scans)
    {
        $gym = $this->gym($token);
        $phone = $this->verifiedPhone($gym);

        if (! $phone) {
            throw ValidationException::withMessages(['phone' => 'Please verify your mobile number with the OTP first.']);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'date_of_birth' => ['required', 'date', 'before:-12 years', 'after:-100 years'],
            'gender' => ['nullable', Rule::in(['male', 'female', 'other'])],
            'height_cm' => ['required', 'numeric', 'between:100,250'],
            'weight_kg' => ['required', 'numeric', 'between:25,300'],
            'fitness_goal' => ['required', Rule::in(array_keys(MemberProfile::GOALS))],
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            // Initial baseline body scan
            'front' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'side' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'back' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'scan_consent' => ['accepted'],
            'emergency_contact_name' => ['required', 'string', 'max:255'],
            'emergency_contact_phone' => ['required', 'string', 'max:20'],
            'membership_plan_id' => ['required', Rule::in(MembershipPlan::availableAt($gym->id)->pluck('id')->all())],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
            'terms' => ['accepted'],
        ], [
            'date_of_birth.before' => 'Members must be at least 12 years old.',
            'membership_plan_id.in' => 'Please choose one of the plans offered at this branch.',
            'scan_consent.accepted' => 'Please confirm you agree to AI analysis of your baseline photos.',
            'front.required' => 'A front body photo is required for your baseline scan.',
            'side.required' => 'A side body photo is required for your baseline scan.',
            'back.required' => 'A back body photo is required for your baseline scan.',
        ]);

        $emergency = OtpService::normalizePhone($data['emergency_contact_phone']);
        if (! $emergency) {
            throw ValidationException::withMessages(['emergency_contact_phone' => 'Enter a valid 10-digit mobile number.']);
        }
        if ($emergency === $phone) {
            throw ValidationException::withMessages(['emergency_contact_phone' => 'Emergency contact must be a different number from yours.']);
        }

        $avatar = $images->storePrivate($request->file('photo'), 'avatars', 800);

        try {
            [$user, $membership] = DB::transaction(function () use ($request, $data, $gym, $phone, $emergency, $avatar, $memberships, $scans) {
                $user = User::create([
                    'gym_id' => $gym->id,
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'phone' => $phone,
                    'avatar_path' => $avatar,
                    'password' => Hash::make($data['password']),
                ]);
                $user->assignRole(Roles::MEMBER);

                $user->memberProfile()->create([
                    'member_code' => MemberProfile::nextCode($gym),
                    'source' => session(self::SOURCE_SESSION_KEY . $gym->id, 'link'),
                    'date_of_birth' => $data['date_of_birth'],
                    'gender' => $data['gender'] ?? null,
                    'height_cm' => $data['height_cm'],
                    'starting_weight_kg' => $data['weight_kg'],
                    'fitness_goal' => $data['fitness_goal'],
                    'emergency_contact_name' => $data['emergency_contact_name'],
                    'emergency_contact_phone' => $emergency,
                    'phone_verified_at' => now(),
                ]);

                // Initial Baseline Scan: photo checks, storage, weight log and audit entry.
                // A failure here rolls back the whole registration.
                $scans->accept(
                    $user,
                    ['front' => $request->file('front'), 'side' => $request->file('side'), 'back' => $request->file('back')],
                    (float) $data['weight_kg'],
                    (float) $data['height_cm'],
                    true
                );

                $membership = $memberships->request($user, MembershipPlan::findOrFail($data['membership_plan_id']));

                return [$user, $membership];
            });
        } catch (\Throwable $e) {
            $images->delete($avatar);
            throw $e;
        }

        $user->notify(new WelcomeMember($user->memberProfile->member_code));

        $request->session()->forget([self::VERIFIED_SESSION_KEY, self::SOURCE_SESSION_KEY . $gym->id]);
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('member.membership')->with('status', "Welcome! Your Member ID is {$user->memberProfile->member_code}. Complete payment, or the front desk can approve your membership.");
    }

    /**
     * Counts one visit per browser session per gym, and remembers whether it started
     * from the printed QR code (?src=qr) so the registration is credited to it.
     */
    private function trackVisit(Request $request, Gym $gym): void
    {
        $key = self::SOURCE_SESSION_KEY . $gym->id;
        $fromQr = $request->query('src') === 'qr';

        if (! $request->session()->has($key)) {
            $source = $fromQr ? 'qr' : 'link';
            $request->session()->put($key, $source);
            JoinVisit::create(['gym_id' => $gym->id, 'source' => $source]);
        } elseif ($fromQr && $request->session()->get($key) !== 'qr') {
            $request->session()->put($key, 'qr');
            JoinVisit::create(['gym_id' => $gym->id, 'source' => 'qr']);
        }
    }

    private function gym(string $token): Gym
    {
        return Gym::where('join_token', $token)->where('is_active', true)->firstOrFail();
    }

    private function phoneFrom(Request $request): string
    {
        $request->validate(['phone' => ['required', 'string', 'max:20']]);
        $phone = OtpService::normalizePhone($request->input('phone'));

        if (! $phone) {
            throw ValidationException::withMessages(['phone' => 'Enter a valid 10-digit Indian mobile number.']);
        }

        return $phone;
    }

    private function verifiedPhone(Gym $gym): ?string
    {
        $v = session(self::VERIFIED_SESSION_KEY);

        if (! $v || $v['gym_id'] !== $gym->id || $v['at'] < now()->subMinutes(self::VERIFIED_TTL_MINUTES)->timestamp) {
            return null;
        }

        return $v['phone'];
    }

    private function mask(string $phone): string
    {
        return substr($phone, 0, 3) . ' ••••• ' . substr($phone, -5);
    }
}
