<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\MemberProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/** Workout Plans, Diet Plans, Notifications and Account Settings. */
class AccountController extends Controller
{
    public function workouts(Request $request)
    {
        $plans = $request->user()->workoutPlans()->with('trainer')->orderByDesc('is_active')->latest()->get();

        return view('member.workouts', ['active' => $plans->firstWhere('is_active', true), 'past' => $plans->where('is_active', false), 'today' => now()->format('l')]);
    }

    public function diet(Request $request)
    {
        $plans = $request->user()->dietPlans()->with('trainer')->orderByDesc('is_active')->latest()->get();

        return view('member.diet', ['active' => $plans->firstWhere('is_active', true), 'past' => $plans->where('is_active', false)]);
    }

    public function payments(Request $request)
    {
        $user = $request->user();

        return view('member.payments', [
            'payments' => \App\Models\Payment::where('user_id', $user->id)->where('status', 'paid')->with('membership.plan:id,name')->latest('paid_at')->get(),
            'memberships' => $user->memberships()->with('plan:id,name,duration_months')->latest()->get(),
            'active' => $user->activeMembership(),
        ]);
    }

    public function notifications(Request $request)
    {
        return view('member.notifications', ['notifications' => $request->user()->notifications()->paginate(20)]);
    }

    public function readNotification(Request $request, string $id)
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();
        $url = $notification->data['url'] ?? null;

        // Only follow links into this app.
        return $url && strpos($url, url('/')) === 0 ? redirect($url) : back();
    }

    public function readAllNotifications(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return back()->with('status', 'All notifications marked as read.');
    }

    public function settings(Request $request)
    {
        $user = $request->user()->load('memberProfile');

        return view('member.settings', ['user' => $user, 'prefs' => $user->memberProfile ? $user->memberProfile->prefs() : MemberProfile::DEFAULT_PREFS]);
    }

    public function updateEmail(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'current_password' => ['required', 'password'],
        ]);
        $user->update(['email' => $data['email']]);

        return back()->with('status', 'Email updated.');
    }

    public function updatePassword(Request $request)
    {
        $data = $request->validate([
            'current_password' => ['required', 'password'],
            'password' => ['required', 'confirmed', 'different:current_password', Password::min(8)->letters()->numbers()],
        ]);

        $user = $request->user();
        $user->forceFill(['password' => Hash::make($data['password'])])->save();
        Auth::logoutOtherDevices($data['password']);

        return back()->with('status', 'Password changed. Other devices have been signed out.');
    }

    public function updatePreferences(Request $request)
    {
        $profile = $request->user()->memberProfile;
        abort_unless($profile, 404);

        $prefs = [];
        foreach (array_keys(MemberProfile::DEFAULT_PREFS) as $key) {
            $prefs[$key] = $request->boolean('prefs.' . $key);
        }
        $profile->update(['notification_prefs' => $prefs]);

        return back()->with('status', 'Notification preferences saved.');
    }
}
