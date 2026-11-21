<?php

namespace App\Services;

use App\Models\Gym;
use App\Models\MemberProfile;
use App\Models\RegistrationRequest;
use App\Models\User;
use App\Notifications\RegistrationRequested;
use App\Notifications\WelcomeMember;
use App\Support\Roles;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * App self-registration: an applicant account plus a request to join one gym at a time.
 * Approval turns the applicant into a member of that gym; rejection keeps everything on
 * record and lets the person apply to another gym.
 */
class RegistrationService
{
    public function register(array $data, string $phone, Gym $gym, string $source): User
    {
        $user = DB::transaction(function () use ($data, $phone, $gym, $source) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $phone,
                // No password chosen: they sign in with mobile + OTP; a random one blocks email login.
                'password' => Hash::make($data['password'] ?? Str::random(40)),
            ]);
            $user->forceFill(['status' => 'applicant'])->save();
            $user->assignRole(Roles::MEMBER);

            $this->createRequest($user, $gym, $source);

            return $user;
        });

        return $user;
    }

    /** Apply to a(nother) gym after a rejection. */
    public function apply(User $user, Gym $gym, string $source): RegistrationRequest
    {
        if (! $user->isApplicant()) {
            throw ValidationException::withMessages(['gym' => 'You are already a member of a gym.']);
        }
        if ($user->registrationRequests()->where('status', 'pending')->exists()) {
            throw ValidationException::withMessages(['gym' => 'Your earlier request is still waiting for the gym\'s answer.']);
        }

        return DB::transaction(fn () => $this->createRequest($user, $gym, $source));
    }

    public function approve(RegistrationRequest $request, User $admin): void
    {
        DB::transaction(function () use ($request, $admin) {
            $request = RegistrationRequest::lockForUpdate()->findOrFail($request->id);
            $this->assertPending($request);
            $user = $request->user;
            $gym = $request->gym;

            $user->forceFill(['gym_id' => $gym->id, 'status' => 'active'])->save();
            $user->memberProfile()->create([
                'member_code' => MemberProfile::nextCode($gym),
                'source' => 'app',
                'phone_verified_at' => now(),
            ]);
            $request->update(['status' => 'approved', 'decided_by' => $admin->id, 'decided_at' => now()]);
        });

        $user = $request->user->fresh('memberProfile');
        $user->notify(new WelcomeMember($user->memberProfile->member_code));
    }

    public function reject(RegistrationRequest $request, User $admin, ?string $reason): void
    {
        DB::transaction(function () use ($request, $admin, $reason) {
            $request = RegistrationRequest::lockForUpdate()->findOrFail($request->id);
            $this->assertPending($request);
            $request->update(['status' => 'rejected', 'rejection_reason' => $reason, 'decided_by' => $admin->id, 'decided_at' => now()]);
        });
    }

    private function createRequest(User $user, Gym $gym, string $source): RegistrationRequest
    {
        $request = $user->registrationRequests()->create(['gym_id' => $gym->id, 'source' => $source]);

        $admins = User::role(Roles::GYM_ADMIN)->where('gym_id', $gym->id)->where('status', 'active')->get();
        Notification::send($admins, new RegistrationRequested($user));

        return $request;
    }

    private function assertPending(RegistrationRequest $request): void
    {
        if (! $request->isPending()) {
            throw ValidationException::withMessages(['request' => 'This request has already been answered.']);
        }
    }
}
