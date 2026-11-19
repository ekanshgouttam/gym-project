<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Gym;
use App\Models\GymSubscription;
use App\Models\PlatformSetting;
use App\Models\User;
use App\Services\ImageStore;
use App\Services\OtpService;
use App\Support\Roles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

/**
 * "Register your gym" — FlexFit SaaS sign-up for gym owners.
 * Creates a new gym (with its own QR join link) and its Gym Owner account, then opens
 * the owner's dashboard. Members never use this form: they join a gym through its QR page.
 */
class RegisterController extends Controller
{
    public function create()
    {
        return view('auth.register', [
            'open' => PlatformSetting::get('allow_gym_registration'),
            'trialDays' => PlatformSetting::get('trial_days'),
        ]);
    }

    public function store(Request $request, ImageStore $images)
    {
        abort_unless(PlatformSetting::get('allow_gym_registration'), 403, 'New gym registrations are paused. Please contact FlexFit.');

        $data = $request->validate([
            'gym_name' => ['required', 'string', 'max:255', 'unique:gyms,name'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:20'],
            'address' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'logo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,gif', 'max:10240'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
            'terms' => ['accepted'],
        ], [
            'gym_name.unique' => 'A gym with this name is already registered. Add your area, e.g. "Gold Gym Andheri".',
            'terms.accepted' => 'Please tick the box to accept the FlexFit terms.',
            'logo.mimes' => 'Upload the logo as a JPG, PNG, WebP or GIF image (SVG and PDF are not supported).',
            'logo.max' => 'The logo must be smaller than 10 MB.',
            'logo.uploaded' => 'The logo could not be uploaded. Try a smaller image.',
            'password.confirmed' => 'The two passwords don\'t match.',
            'email.unique' => 'An account with this email already exists. Sign in instead, or use another email.',
        ], [
            'gym_name' => 'gym name',
            'name' => 'owner name',
        ]);

        $phone = OtpService::normalizePhone($data['phone']);
        if (! $phone) {
            throw ValidationException::withMessages(['phone' => 'Enter a valid 10-digit Indian mobile number.']);
        }
        if (User::where('phone', $phone)->exists()) {
            throw ValidationException::withMessages(['phone' => 'This mobile number is already registered. Please sign in instead.']);
        }

        $logo = $request->hasFile('logo') ? $images->storeLogo($request->file('logo')) : null;

        try {
            $user = DB::transaction(function () use ($data, $phone, $logo) {
                $gym = Gym::create([
                    'name' => $data['gym_name'],
                    'slug' => Gym::uniqueSlug($data['gym_name']),
                    'email' => $data['email'],
                    'phone' => $phone,
                    'address' => $data['address'],
                    'city' => $data['city'],
                    'logo_path' => $logo,
                    'is_active' => true,
                ]);

                $user = User::create([
                    'gym_id' => $gym->id,
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'phone' => $phone,
                    'password' => Hash::make($data['password']),
                ]);
                $user->assignRole(Roles::GYM_ADMIN);

                $gym->forceFill(['owner_id' => $user->id])->save();
                GymSubscription::startTrial($gym);

                return $user;
            });
        } catch (\Throwable $e) {
            $images->delete($logo);
            throw $e;
        }

        Auth::login($user);
        $request->session()->regenerate();
        $user->forceFill(['last_login_at' => now()])->save();

        return redirect()->route('gym-admin.dashboard')
            ->with('status', "Welcome to FlexFit! {$user->gym->name} is live. Print your QR code so members can register.");
    }
}
