<?php

namespace App\Services;

use App\Models\Gym;
use App\Models\MemberProfile;
use App\Models\Membership;
use App\Models\MembershipPlan;
use App\Models\User;
use App\Notifications\MembershipActivated;
use App\Notifications\WelcomeMember;
use App\Support\Roles;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Creates a member registered by gym staff (front desk form or CSV import):
 * account, Member ID, profile and, optionally, a membership that is either already
 * paid (active now) or waiting for payment.
 */
class MemberRegistrationService
{
    /**
     * @param array{name:string, email:string, phone:string, date_of_birth?:?string, gender?:?string, height_cm?:?float,
     *              weight_kg?:?float, fitness_goal?:?string, emergency_contact_name?:?string, emergency_contact_phone?:?string,
     *              password?:?string} $data  phone already normalised (+91...)
     */
    public function register(Gym $gym, array $data, string $source, User $staff, ?MembershipPlan $plan = null, bool $paid = false, bool $notify = true): User
    {
        $user = DB::transaction(function () use ($gym, $data, $source, $staff, $plan, $paid) {
            $user = User::create([
                'gym_id' => $gym->id,
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                // Without a password the member sets one with "Forgot password" on first login.
                'password' => Hash::make($data['password'] ?? Str::random(40)),
            ]);
            $user->assignRole(Roles::MEMBER);

            $user->memberProfile()->create([
                'member_code' => MemberProfile::nextCode($gym),
                'source' => $source,
                'registered_by' => $staff->id,
                'trainer_id' => $data['trainer_id'] ?? null,
                'date_of_birth' => $data['date_of_birth'] ?? null,
                'gender' => $data['gender'] ?? null,
                'height_cm' => $data['height_cm'] ?? null,
                'starting_weight_kg' => $data['weight_kg'] ?? null,
                'fitness_goal' => $data['fitness_goal'] ?? null,
                'emergency_contact_name' => $data['emergency_contact_name'] ?? null,
                'emergency_contact_phone' => $data['emergency_contact_phone'] ?? null,
            ]);

            if ($plan) {
                $membership = Membership::create([
                    'user_id' => $user->id,
                    'gym_id' => $gym->id,
                    'membership_plan_id' => $plan->id,
                    'status' => 'pending',
                    'amount' => $plan->price,
                    'notes' => 'Added by ' . $staff->name . ($source === 'import' ? ' (CSV import)' : ''),
                ]);
                if ($paid) {
                    $membership->setRelation('plan', $plan);
                    $membership->activate('admin', $staff);
                    app(MembershipService::class)->recordDeskPayment($membership, $staff, $data['payment_method'] ?? 'desk', $source === 'import' ? 'CSV import' : null);
                }
            }

            return $user;
        });

        if ($notify) {
            $user->notify(new WelcomeMember($user->memberProfile->member_code));
            $active = $user->memberships()->where('status', 'active')->with('plan')->first();
            if ($active) {
                $user->notify(new MembershipActivated($active));
            }
        }

        return $user;
    }
}
