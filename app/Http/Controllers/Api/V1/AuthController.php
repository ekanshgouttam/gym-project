<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Branding;
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

        if (! $user->isActive()) {
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

    public function me(Request $request)
    {
        return response()->json(['user' => self::userPayload($request->user())]);
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
        ];
    }
}
