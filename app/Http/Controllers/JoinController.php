<?php

namespace App\Http\Controllers;

use App\Models\Gym;
use App\Models\JoinVisit;
use App\Models\MemberProfile;
use App\Models\MembershipPlan;
use App\Models\User;
use App\Services\MemberRegistration;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

    public function store(Request $request, string $token, MemberRegistration $registration)
    {
        $gym = $this->gym($token);
        $phone = $this->verifiedPhone($gym);

        if (! $phone) {
            throw ValidationException::withMessages(['phone' => 'Please verify your mobile number with the OTP first.']);
        }

        $user = $registration->register($request, $gym, $phone, session(self::SOURCE_SESSION_KEY . $gym->id, 'link'));

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
