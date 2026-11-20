<?php

namespace App\Services;

use App\Models\Gym;
use App\Models\MemberProfile;
use App\Models\MembershipPlan;
use App\Models\User;
use App\Notifications\WelcomeMember;
use App\Support\Roles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

/**
 * Creates a member who joined a gym through its QR join link — used by the website
 * (JoinController) and the mobile app (Api\V1\JoinController). The caller must have
 * verified $phone with an OTP first.
 */
class MemberRegistration
{
    /** @var ImageStore */
    private $images;
    /** @var MembershipService */
    private $memberships;
    /** @var BodyScanService */
    private $scans;

    public function __construct(ImageStore $images, MembershipService $memberships, BodyScanService $scans)
    {
        $this->images = $images;
        $this->memberships = $memberships;
        $this->scans = $scans;
    }

    public function register(Request $request, Gym $gym, string $phone, string $source): User
    {
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

        $avatar = $this->images->storePrivate($request->file('photo'), 'avatars', 800);

        try {
            $user = DB::transaction(function () use ($request, $data, $gym, $phone, $emergency, $avatar, $source) {
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
                    'source' => $source,
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
                $this->scans->accept(
                    $user,
                    ['front' => $request->file('front'), 'side' => $request->file('side'), 'back' => $request->file('back')],
                    (float) $data['weight_kg'],
                    (float) $data['height_cm'],
                    true
                );

                $this->memberships->request($user, MembershipPlan::findOrFail($data['membership_plan_id']));

                return $user;
            });
        } catch (\Throwable $e) {
            $this->images->delete($avatar);
            throw $e;
        }

        $user->notify(new WelcomeMember($user->memberProfile->member_code));

        return $user;
    }
}
