<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Gym;
use App\Models\MemberProfile;
use App\Models\MembershipPlan;
use App\Models\OtpVerification;
use App\Models\User;
use App\Services\MemberRegistration;
use App\Services\OtpService;
use App\Support\Branding;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Member registration from the mobile app. Same flow as the website's QR join page:
 * the app reads the join token from the gym's QR code (/join/{token}), verifies the
 * mobile number with an OTP, then registers. Registration returns a login token.
 */
class JoinController extends Controller
{
    private const VERIFIED_TTL_MINUTES = 30;

    public function show(string $token)
    {
        $gym = $this->gym($token);

        return response()->json([
            'gym' => ['id' => $gym->id, 'name' => $gym->name, 'address' => $gym->address, 'city' => $gym->city, 'branding' => Branding::forApi($gym)],
            'plans' => MembershipPlan::availableAt($gym->id)->get()->map(function (MembershipPlan $plan) {
                return [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'duration_months' => $plan->duration_months,
                    'price' => $plan->price,
                    'description' => $plan->description,
                    'features' => $plan->features,
                ];
            }),
            'goals' => MemberProfile::GOALS,
        ]);
    }

    public function sendOtp(Request $request, string $token, OtpService $otp)
    {
        $gym = $this->gym($token);
        $phone = $this->phoneFrom($request);

        if (User::where('phone', $phone)->exists()) {
            throw ValidationException::withMessages(['phone' => 'This mobile number is already registered. Please sign in instead.']);
        }

        $otp->send($phone, $this->purpose($gym), $request->ip());

        return response()->json([
            'message' => 'We sent a 6-digit code to your mobile number.',
            'resend_after' => OtpService::RESEND_COOLDOWN_SECONDS,
        ]);
    }

    public function verifyOtp(Request $request, string $token, OtpService $otp)
    {
        $gym = $this->gym($token);
        $phone = $this->phoneFrom($request);
        $request->validate(['code' => ['required', 'digits:6']]);

        if (! $otp->verify($phone, $this->purpose($gym), $request->input('code'))) {
            throw ValidationException::withMessages(['code' => 'That code is incorrect or has expired.']);
        }

        return response()->json([
            'message' => 'Mobile number verified. Complete registration within ' . self::VERIFIED_TTL_MINUTES . ' minutes.',
            'phone' => $phone,
        ]);
    }

    /** Multipart form: the same fields as the website join form, plus "phone" and "device_name". */
    public function store(Request $request, string $token, MemberRegistration $registration)
    {
        $gym = $this->gym($token);
        $phone = $this->phoneFrom($request);
        $request->validate(['device_name' => ['required', 'string', 'max:255']]);

        // No session in the API: the OTP row marked verified by verifyOtp() is the proof.
        $verified = OtpVerification::where('phone', $phone)->where('purpose', $this->purpose($gym))
            ->where('verified_at', '>=', now()->subMinutes(self::VERIFIED_TTL_MINUTES));

        if (! $verified->exists()) {
            throw ValidationException::withMessages(['phone' => 'Please verify your mobile number with the OTP first.']);
        }

        $user = $registration->register($request, $gym, $phone, 'app');
        $verified->delete();

        return response()->json([
            'message' => "Welcome! Your Member ID is {$user->memberProfile->member_code}. Complete payment, or the front desk can approve your membership.",
            'token' => $user->createToken($request->input('device_name'))->plainTextToken,
            'user' => AuthController::userPayload($user),
        ], 201);
    }

    private function gym(string $token): Gym
    {
        return Gym::where('join_token', $token)->where('is_active', true)->firstOrFail();
    }

    private function purpose(Gym $gym): string
    {
        return 'join:' . $gym->id;
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
}
