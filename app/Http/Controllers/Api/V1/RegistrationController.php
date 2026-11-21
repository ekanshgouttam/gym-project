<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Gym;
use App\Models\User;
use App\Services\OtpService;
use App\Services\RegistrationService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

/**
 * Self-registration from the app: pick a gym (list or QR), verify the mobile number with an
 * OTP, and send a join request that the gym admin approves or rejects.
 */
class RegistrationController extends Controller
{
    private const OTP_PURPOSE = 'register';

    /** Active gyms to choose from, optionally filtered by name or city. */
    public function gyms(Request $request)
    {
        $q = trim((string) $request->query('q'));

        $gyms = Gym::where('is_active', true)
            ->when($q !== '', fn ($query) => $query->where(fn ($w) => $w->where('name', 'like', "%{$q}%")->orWhere('city', 'like', "%{$q}%")))
            ->orderBy('name')->limit(50)->get();

        return response()->json(['gyms' => $gyms->map(fn (Gym $gym) => [
            'id' => $gym->id, 'name' => $gym->name, 'city' => $gym->city, 'address' => $gym->address, 'logo_url' => $gym->logoUrl(),
        ])]);
    }

    public function sendOtp(Request $request, OtpService $otp)
    {
        $phone = $this->phoneFrom($request);
        if (User::where('phone', $phone)->exists()) {
            throw ValidationException::withMessages(['phone' => 'This mobile number is already registered. Please log in instead.']);
        }

        $otp->send($phone, self::OTP_PURPOSE, $request->ip());

        return response()->json([
            'message' => 'We sent a 6-digit code to your mobile number.',
            'resend_after' => OtpService::RESEND_COOLDOWN_SECONDS,
        ]);
    }

    public function register(Request $request, OtpService $otp, RegistrationService $registrations)
    {
        $phone = $this->phoneFrom($request);
        $data = $request->validate([
            'code' => ['required', 'digits:6'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['nullable', 'confirmed', Password::min(8)->letters()->numbers()],
            'device_name' => ['required', 'string', 'max:255'],
        ], ['email.unique' => 'An account with this email already exists. Please log in instead.']);
        [$gym, $source] = $this->gymFrom($request);

        if (User::where('phone', $phone)->exists()) {
            throw ValidationException::withMessages(['phone' => 'This mobile number is already registered. Please log in instead.']);
        }
        if (! $otp->verify($phone, self::OTP_PURPOSE, $data['code'])) {
            throw ValidationException::withMessages(['code' => 'That code is incorrect or has expired.']);
        }

        $user = $registrations->register($data, $phone, $gym, $source);

        return response()->json([
            'message' => "Your request to join {$gym->name} has been sent. You can use the app once the gym approves it.",
            'token' => $user->createToken($data['device_name'])->plainTextToken,
            'user' => AuthController::userPayload($user),
        ], 201);
    }

    /** Where the applicant's request stands (also part of the user payload). */
    public function status(Request $request)
    {
        return response()->json(['registration' => AuthController::registrationPayload($request->user())]);
    }

    /** Apply to another gym after a rejection. */
    public function apply(Request $request, RegistrationService $registrations)
    {
        [$gym, $source] = $this->gymFrom($request);
        $registrations->apply($request->user(), $gym, $source);

        return response()->json([
            'message' => "Your request to join {$gym->name} has been sent.",
            'registration' => AuthController::registrationPayload($request->user()->fresh()),
        ], 201);
    }

    /** The gym from "gym_id" (picked from the list) or "join_token" (scanned QR code). */
    private function gymFrom(Request $request): array
    {
        $request->validate([
            'gym_id' => ['required_without:join_token', 'nullable', 'integer'],
            'join_token' => ['required_without:gym_id', 'nullable', 'string', 'max:64'],
        ], ['gym_id.required_without' => 'Choose a gym, or scan its QR code.']);

        $gym = $request->filled('join_token')
            ? Gym::where('join_token', $request->input('join_token'))->where('is_active', true)->first()
            : Gym::where('id', $request->input('gym_id'))->where('is_active', true)->first();

        if (! $gym) {
            throw ValidationException::withMessages(['gym_id' => 'That gym was not found.']);
        }

        return [$gym, $request->filled('join_token') ? 'qr' : 'app'];
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
