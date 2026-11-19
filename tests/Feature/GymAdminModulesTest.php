<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\DietPlan;
use App\Models\Enquiry;
use App\Models\Gym;
use App\Models\Membership;
use App\Models\MembershipPlan;
use App\Models\Payment;
use App\Models\User;
use App\Models\WorkoutPlan;
use App\Support\Roles;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GymAdminModulesTest extends TestCase
{
    use RefreshDatabase;

    private Gym $gym;
    private User $owner;
    private User $trainer;
    private MembershipPlan $plan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->gym = Gym::create(['name' => 'Alpha', 'slug' => 'alpha', 'is_active' => true]);
        $this->owner = $this->user(Roles::GYM_ADMIN);
        $this->trainer = $this->user(Roles::TRAINER);
        $this->plan = MembershipPlan::create(['gym_id' => $this->gym->id, 'name' => 'Monthly', 'slug' => 'monthly', 'duration_months' => 1, 'price' => 1500, 'features' => []]);
    }

    private function user(string $role, ?Gym $gym = null): User
    {
        $u = User::factory()->create(['gym_id' => ($gym ?? $this->gym)->id, 'phone' => '+9198' . random_int(10000000, 99999999)]);
        $u->assignRole($role);
        if ($role === Roles::MEMBER) {
            $u->memberProfile()->create(['member_code' => 'IF-T-' . $u->id]);
        }

        return $u;
    }

    private function activeMembership(User $member, string $ends): Membership
    {
        return Membership::create(['user_id' => $member->id, 'gym_id' => $this->gym->id, 'membership_plan_id' => $this->plan->id,
            'status' => 'active', 'amount' => 1500, 'starts_on' => now()->subMonth(), 'ends_on' => $ends]);
    }

    public function test_every_owner_page_loads_and_trainers_only_reach_the_plan_builder()
    {
        $member = $this->user(Roles::MEMBER);
        $enquiry = Enquiry::create(['gym_id' => $this->gym->id, 'name' => 'Lead', 'phone' => '+919800000001', 'source' => 'walk_in']);
        $pages = [
            route('gym-admin.dashboard'), route('gym-admin.enquiries.index'), route('gym-admin.enquiries.show', $enquiry),
            route('gym-admin.fees.index'), route('gym-admin.fees.index', ['tab' => 'plans']), route('gym-admin.fees.plans.create'),
            route('gym-admin.fees.plans.edit', $this->plan), route('gym-admin.fees.renew', $member), route('gym-admin.payments.index'),
            route('gym-admin.attendance.index', ['q' => $member->name]), route('gym-admin.trainers.index'), route('gym-admin.trainers.edit', $this->trainer),
            route('gym-admin.transformation'), route('gym-admin.reports'), route('gym-admin.settings'),
        ];
        foreach ($pages as $url) {
            $this->actingAs($this->owner)->get($url)->assertOk();
            $this->actingAs($this->trainer)->get($url)->assertForbidden();
        }

        foreach ([route('gym-admin.plans.index'), route('gym-admin.plans.index', ['kind' => 'diet']), route('gym-admin.plans.create', 'workout'), route('gym-admin.plans.create', 'diet')] as $url) {
            $this->actingAs($this->owner)->get($url)->assertOk();
            $this->actingAs($this->trainer)->get($url)->assertOk();
        }
        $this->actingAs($member)->get(route('gym-admin.plans.index'))->assertForbidden();
        $this->actingAs($member)->get(route('member.payments'))->assertOk();

        // Another gym's enquiry is invisible.
        $other = Gym::create(['name' => 'Beta', 'slug' => 'beta']);
        $foreign = Enquiry::create(['gym_id' => $other->id, 'name' => 'X', 'phone' => '+919800000002', 'source' => 'phone']);
        $this->actingAs($this->owner)->get(route('gym-admin.enquiries.show', $foreign))->assertNotFound();
    }

    public function test_enquiry_is_followed_up_and_converted_into_a_member()
    {
        $this->actingAs($this->owner)->post(route('gym-admin.enquiries.store'), [
            'name' => 'Neha', 'phone' => '98200 77777', 'source' => 'instagram', 'interest' => 'Monthly', 'next_follow_up_on' => today()->toDateString(),
        ])->assertRedirect();
        $enquiry = Enquiry::firstOrFail();
        $this->assertSame('+919820077777', $enquiry->phone);
        $this->get(route('gym-admin.dashboard'))->assertSee('Neha');

        $this->put(route('gym-admin.enquiries.update', $enquiry), [
            'name' => 'Neha', 'phone' => '9820077777', 'source' => 'instagram', 'status' => 'trial', 'note' => 'Trial on Saturday',
        ])->assertRedirect();
        $this->assertSame('trial', $enquiry->fresh()->status);
        $this->assertSame(3, $enquiry->activities()->count());

        // Can't mark converted by hand.
        $this->put(route('gym-admin.enquiries.update', $enquiry), ['name' => 'Neha', 'phone' => '9820077777', 'source' => 'instagram', 'status' => 'converted'])
            ->assertSessionHasErrors('status');

        $this->get(route('manage.members.create', ['enquiry' => $enquiry->id]))->assertOk()->assertSee('Converting enquiry');
        $this->post(route('manage.members.store'), [
            'name' => 'Neha', 'email' => 'neha@example.test', 'phone' => '9820077777', 'enquiry_id' => $enquiry->id,
            'membership_plan_id' => $this->plan->id, 'payment' => 'paid', 'payment_method' => 'upi', 'trainer_id' => $this->trainer->id,
        ])->assertRedirect();

        $enquiry->refresh();
        $member = User::where('email', 'neha@example.test')->firstOrFail();
        $this->assertSame('converted', $enquiry->status);
        $this->assertSame($member->id, $enquiry->converted_user_id);
        $this->assertSame($this->trainer->id, $member->memberProfile->trainer_id);
        $this->assertSame('upi', Payment::where('user_id', $member->id)->first()->gateway);
        $this->get(route('gym-admin.enquiries.index'))->assertSee('100'); // conversion rate
    }

    public function test_renewal_collects_fee_extends_from_current_end_and_shows_in_payments()
    {
        $member = $this->user(Roles::MEMBER);
        $current = $this->activeMembership($member, today()->addDays(5)->toDateString());

        $this->actingAs($this->owner)->post(route('gym-admin.fees.renew.store', $member), [
            'membership_plan_id' => $this->plan->id, 'method' => 'cash', 'amount' => 1200, 'note' => 'Diwali offer',
        ])->assertRedirect(route('gym-admin.fees.index'));

        $new = Membership::where('user_id', $member->id)->where('id', '!=', $current->id)->firstOrFail();
        $this->assertSame('active', $new->status);
        $this->assertSame(today()->addDays(6)->toDateString(), $new->starts_on->toDateString());
        $payment = Payment::where('membership_id', $new->id)->firstOrFail();
        $this->assertSame(120000, $payment->amount_paise);
        $this->assertSame($this->owner->id, $payment->recorded_by);

        $this->get(route('gym-admin.payments.index'))->assertSee('Diwali offer')->assertSee('1,200');
        $this->get(route('gym-admin.payments.export'))->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->actingAs($member)->get(route('member.payments'))->assertSee('1,200');
    }

    public function test_approving_a_desk_payment_records_it()
    {
        $member = $this->user(Roles::MEMBER);
        $m = Membership::create(['user_id' => $member->id, 'gym_id' => $this->gym->id, 'membership_plan_id' => $this->plan->id, 'status' => 'pending', 'amount' => 1500]);

        $this->actingAs($this->owner)->post(route('manage.memberships.approve', $m), ['method' => 'card'])->assertRedirect();
        $this->assertSame('active', $m->fresh()->status);
        $this->assertSame('card', Payment::where('membership_id', $m->id)->value('gateway'));
    }

    public function test_desk_check_in_and_out()
    {
        $member = $this->user(Roles::MEMBER);
        $this->actingAs($this->owner)->post(route('gym-admin.attendance.check-in', $member))->assertRedirect()->assertSessionHas('status');
        $this->post(route('gym-admin.attendance.check-in', $member));
        $this->assertSame(1, Attendance::count());
        $visit = Attendance::firstOrFail();
        $this->assertSame('desk', $visit->method);

        $this->post(route('gym-admin.attendance.check-out', $visit))->assertRedirect();
        $this->assertNotNull($visit->fresh()->checked_out_at);

        $foreign = $this->user(Roles::MEMBER, Gym::create(['name' => 'Beta', 'slug' => 'beta']));
        $this->post(route('gym-admin.attendance.check-in', $foreign))->assertNotFound();
    }

    public function test_trainer_profile_and_member_assignment()
    {
        $a = $this->user(Roles::MEMBER);
        $b = $this->user(Roles::MEMBER);

        $this->actingAs($this->owner)->put(route('gym-admin.trainers.update', $this->trainer), [
            'specialty' => 'Strength', 'experience_years' => 6, 'is_public' => '1', 'members' => [$a->id, $b->id],
        ])->assertRedirect();
        $this->assertSame('Strength', $this->trainer->fresh()->trainerProfile->specialty);
        $this->assertSame($this->trainer->id, $a->memberProfile->fresh()->trainer_id);

        $this->put(route('gym-admin.trainers.update', $this->trainer), ['specialty' => 'Strength', 'members' => [$b->id]]);
        $this->assertNull($a->memberProfile->fresh()->trainer_id);
        $this->assertSame($this->trainer->id, $b->memberProfile->fresh()->trainer_id);
    }

    public function test_plan_builder_assigns_copies_and_archives_previous_plans()
    {
        $a = $this->user(Roles::MEMBER);
        $b = $this->user(Roles::MEMBER);
        $old = WorkoutPlan::create(['member_id' => $a->id, 'title' => 'Old', 'days' => [], 'is_active' => true]);

        $this->actingAs($this->trainer)->post(route('gym-admin.plans.store', 'workout'), [
            'title' => 'Push Pull Legs', 'goal' => 'Hypertrophy', 'member_ids' => [$a->id, $b->id],
            'days' => [
                ['day' => 'Monday', 'focus' => 'Push', 'exercises' => "Bench press | 4 | 8-10 | 90s\nDips | 3 | 12"],
                ['day' => 'Tuesday', 'focus' => '', 'exercises' => ''],
            ],
        ])->assertRedirect();

        $this->assertFalse($old->fresh()->is_active);
        $plan = WorkoutPlan::where('member_id', $a->id)->where('is_active', true)->firstOrFail();
        $this->assertSame($this->trainer->id, $plan->trainer_id);
        $this->assertCount(1, $plan->days);
        $this->assertSame(['name' => 'Bench press', 'sets' => '4', 'reps' => '8-10', 'rest' => '90s'], $plan->days[0]['exercises'][0]);
        $this->assertSame(2, WorkoutPlan::where('title', 'Push Pull Legs')->count());
        $this->assertSame(1, $a->notifications()->count());
        $this->actingAs($a)->get(route('member.workouts'))->assertSee('Bench press');

        $this->actingAs($this->owner)->post(route('gym-admin.plans.store', 'diet'), [
            'title' => 'Lean', 'daily_calories' => 2000, 'protein' => 140, 'member_ids' => [$b->id],
            'meals' => [['time' => '08:00', 'name' => 'Breakfast', 'items' => "Oats\nEggs"], ['time' => '', 'name' => '', 'items' => '']],
        ])->assertRedirect();
        $diet = DietPlan::where('member_id', $b->id)->firstOrFail();
        $this->assertSame(['protein' => 140], $diet->macros);
        $this->assertSame(['Oats', 'Eggs'], $diet->meals[0]['items']);

        // Empty plan is rejected; members of other gyms can't be targeted.
        $this->post(route('gym-admin.plans.store', 'workout'), ['title' => 'Empty', 'member_ids' => [$a->id], 'days' => []])->assertSessionHasErrors('days');
        $foreign = $this->user(Roles::MEMBER, Gym::create(['name' => 'Beta', 'slug' => 'beta']));
        $this->post(route('gym-admin.plans.store', 'diet'), ['title' => 'X', 'member_ids' => [$foreign->id], 'meals' => [['name' => 'A', 'items' => 'B']]])->assertSessionHasErrors('member_ids.0');
    }

    public function test_reminder_command_sends_each_reminder_once_and_expires_memberships()
    {
        $soon = $this->user(Roles::MEMBER);
        $ended = $this->user(Roles::MEMBER);
        $unpaid = $this->user(Roles::MEMBER);
        $this->activeMembership($soon, today()->addDays(3)->toDateString());
        $old = $this->activeMembership($ended, today()->subDay()->toDateString());
        $pending = Membership::create(['user_id' => $unpaid->id, 'gym_id' => $this->gym->id, 'membership_plan_id' => $this->plan->id, 'status' => 'pending', 'amount' => 1500]);
        $pending->forceFill(['created_at' => now()->subDays(3)])->save();

        $this->artisan('memberships:remind')->assertExitCode(0);
        $this->artisan('memberships:remind')->assertExitCode(0);

        $this->assertSame('expired', $old->fresh()->status);
        $this->assertSame(1, $soon->notifications()->count());
        $this->assertStringContainsString('3 days', $soon->notifications()->first()->data['title']);
        $this->assertSame(1, $ended->notifications()->count());
        $this->assertSame(1, $unpaid->notifications()->count());
        $this->assertSame(1, $this->owner->notifications()->count());

        // Owner can switch reminders off.
        $this->actingAs($this->owner)->put(route('gym-admin.settings.update'), ['name' => 'Alpha', 'opening_hours' => '6am–10pm'])->assertRedirect();
        $gym = $this->gym->fresh();
        $this->assertFalse($gym->setting('expiry_reminders'));
        $this->assertSame('6am–10pm', $gym->setting('opening_hours'));
        $this->get(route('gyms.show', $gym))->assertSee('6am–10pm');
    }

    public function test_reports_and_member_export()
    {
        $member = $this->user(Roles::MEMBER);
        $this->activeMembership($member, today()->addMonth()->toDateString());

        $this->actingAs($this->owner)->get(route('gym-admin.reports'))->assertOk()->assertSee('Win them back')->assertSee($member->name);
        $csv = $this->get(route('gym-admin.reports.members'));
        $csv->assertOk();
        $this->assertStringContainsString($member->name, $csv->streamedContent());
    }
}
