<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\MemberProfile;
use App\Services\ImageStore;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user()->load('memberProfile', 'gym');

        return view('member.profile', [
            'user' => $user,
            'profile' => $user->memberProfile ?: new MemberProfile(),
            'goals' => MemberProfile::GOALS,
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'date_of_birth' => ['nullable', 'date', 'before:-12 years'],
            'gender' => ['nullable', Rule::in(['male', 'female', 'other'])],
            'height_cm' => ['nullable', 'numeric', 'between:100,250'],
            'target_weight_kg' => ['nullable', 'numeric', 'between:25,300'],
            'fitness_goal' => ['nullable', Rule::in(array_keys(MemberProfile::GOALS))],
            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:20'],
        ]);

        if (! empty($data['emergency_contact_phone'])) {
            $data['emergency_contact_phone'] = OtpService::normalizePhone($data['emergency_contact_phone']);
            if (! $data['emergency_contact_phone']) {
                throw ValidationException::withMessages(['emergency_contact_phone' => 'Enter a valid 10-digit mobile number.']);
            }
        }

        $user->update(['name' => $data['name']]);
        unset($data['name']);

        $profile = $user->memberProfile;
        if ($profile) {
            $profile->update($data);
        }

        return back()->with('status', 'Profile updated.');
    }

    public function updatePhoto(Request $request, ImageStore $images)
    {
        $request->validate(['photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192']]);
        $user = $request->user();

        $old = $user->avatar_path;
        $user->update(['avatar_path' => $images->storePrivate($request->file('photo'), 'avatars', 800)]);
        $images->delete($old);

        return back()->with('status', 'Profile photo updated.');
    }
}
