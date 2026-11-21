<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\OtpService;
use App\Support\Branding;
use App\Support\Roles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Mobile app sign-in for every role. Login returns a Sanctum token that the app
 * sends as "Authorization: Bearer <token>" on later requests.
 */
class AuthController extends Controller
{
    private const MAX_ATTEMPTS = 5;
    private const OTP_PURPOSE = 'login';

    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['required', 'string', 'max:255'],
        ]);

        $throttleKey = Str::lower($data['email']) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            throw ValidationException::withMessages([
                'email' => "Too many login attempts. Try again in {$seconds} seconds.",
            ]);
        }

        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            RateLimiter::hit($throttleKey);
            throw ValidationException::withMessages([
                'email' => 'These credentials do not match our records.',
            ]);
        }

        RateLimiter::clear($throttleKey);

        // Applicants may sign in: the app shows them their registration status.
        if (! $user->isActive() && ! $user->isApplicant()) {
            throw ValidationException::withMessages([
                'email' => 'Your account has been suspended. Please contact the gym.',
            ]);
        }

        $user->forceFill(['last_login_at' => now()])->save();

        return response()->json([
            'token' => $user->createToken($data['device_name'])->plainTextToken,
            'user' => self::userPayload($user),
        ]);
    }

    /** Member login with mobile number, step 1: text a one-time code to the registered number. */
    public function sendLoginOtp(Request $request, OtpService $otp)
    {
        $member = $this->memberByPhone($request);
        $otp->send($member->phone, self::OTP_PURPOSE, $request->ip());

        return response()->json([
            'message' => 'We sent a 6-digit code to your mobile number.',
            'resend_after' => OtpService::RESEND_COOLDOWN_SECONDS,
        ]);
    }

    /** Member login with mobile number, step 2: check the code and return a token. */
    public function verifyLoginOtp(Request $request, OtpService $otp)
    {
        $member = $this->memberByPhone($request);
        $data = $request->validate([
            'code' => ['required', 'digits:6'],
            'device_name' => ['required', 'string', 'max:255'],
        ]);

        if (! $otp->verify($member->phone, self::OTP_PURPOSE, $data['code'])) {
            throw ValidationException::withMessages(['code' => 'That code is incorrect or has expired.']);
        }

        $member->forceFill(['last_login_at' => now()])->save();

        return response()->json([
            'token' => $member->createToken($data['device_name'])->plainTextToken,
            'user' => self::userPayload($member),
        ]);
    }

    private function memberByPhone(Request $request): User
    {
        $request->validate(['phone' => ['required', 'string', 'max:20']]);
        $phone = OtpService::normalizePhone($request->input('phone'));
        if (! $phone) {
            throw ValidationException::withMessages(['phone' => 'Enter a valid 10-digit Indian mobile number.']);
        }

        $member = User::role(Roles::MEMBER)->where('phone', $phone)->first();
        if (! $member) {
            throw ValidationException::withMessages(['phone' => 'No member account uses this mobile number. Join your gym first, or sign in with email.']);
        }
        if (! $member->isActive() && ! $member->isApplicant()) {
            throw ValidationException::withMessages(['phone' => 'Your account has been suspended. Please contact the gym.']);
        }

        return $member;
    }

    public function me(Request $request)
    {
        return response()->json(['user' => self::userPayload($request->user())]);
    }

    /** The signed-in user's gym branding, so the app can restyle without logging in again. */
    public function branding(Request $request)
    {
        $gym = $request->user()->gym;

        return response()->json(['branding' => $gym ? Branding::forApi($gym) : null]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Signed out.']);
    }

    public static function userPayload(User $user): array
    {
        $user->loadMissing('gym', 'memberProfile:id,user_id,member_code');

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'role' => $user->primaryRole(),
            'member_code' => optional($user->memberProfile)->member_code,
            'gym' => $user->gym ? ['id' => $user->gym->id, 'name' => $user->gym->name, 'branding' => Branding::forApi($user->gym)] : null,
            // "active" = full access; "applicant" = waiting for (or rejected by) a gym, see "registration".
            'status' => $user->status,
            'registration' => self::registrationPayload($user),
        ];
    }

    /** The user's latest join request from the app, or null if they never self-registered. */
    public static function registrationPayload(User $user): ?array
    {
        $request = $user->latestRegistration()->with('gym:id,name')->first();

        return $request ? [
            'status' => $request->status,
            'gym' => ['id' => $request->gym->id, 'name' => $request->gym->name],
            'rejection_reason' => $request->rejection_reason,
            'requested_at' => $request->created_at->toIso8601String(),
            'decided_at' => optional($request->decided_at)->toIso8601String(),
        ] : null;
    }
}
