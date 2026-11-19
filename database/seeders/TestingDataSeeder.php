<?php

namespace Database\Seeders;

use App\Models\AiUsageLog;
use App\Models\Attendance;
use App\Models\DemoRequest;
use App\Models\DietPlan;
use App\Models\Enquiry;
use App\Models\Gym;
use App\Models\GymSubscription;
use App\Models\JoinVisit;
use App\Models\MemberProfile;
use App\Models\Membership;
use App\Models\MembershipPlan;
use App\Models\Payment;
use App\Models\SubscriptionPayment;
use App\Models\SubscriptionPlan;
use App\Models\SupportRequest;
use App\Models\TrainerProfile;
use App\Models\User;
use App\Models\WeightLog;
use App\Models\WorkoutPlan;
use App\Support\Roles;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Rich local testing data on top of DemoDataSeeder, so every screen of the owner panel,
 * member app and Super Admin panel has something to show. Local use only.
 *
 *   php artisan db:seed --class=TestingDataSeeder
 *
 * Safe to run again: each section skips itself if its data is already there.
 * All accounts use the password Password@123.
 */
class TestingDataSeeder extends Seeder
{
    private const FIRST = ['Aarav', 'Vivaan', 'Aditya', 'Ishaan', 'Kabir', 'Riya', 'Ananya', 'Diya', 'Meera', 'Sneha', 'Rahul', 'Karan', 'Neha', 'Pooja', 'Vikram', 'Sanya', 'Arjun', 'Tanvi', 'Nikhil', 'Isha', 'Rohit', 'Simran', 'Harsh', 'Aisha', 'Dev', 'Kritika', 'Manav', 'Nidhi'];
    private const LAST = ['Sharma', 'Verma', 'Patel', 'Reddy', 'Iyer', 'Nair', 'Gupta', 'Singh', 'Mehta', 'Joshi', 'Kulkarni', 'Desai', 'Chopra', 'Bose', 'Rao', 'Malhotra', 'Kapoor', 'Agarwal', 'Pillai', 'Saxena'];

    private $phoneSeq = 0;

    public function run()
    {
        if (! app()->environment('local', 'testing')) {
            $this->command->error('TestingDataSeeder only runs in a local environment.');

            return;
        }
        mt_srand(2026);

        if (! User::where('email', 'admin@gym.test')->exists()) {
            $this->call(DemoDataSeeder::class);
        }

        $andheri = Gym::where('slug', 'ironforge-andheri')->firstOrFail();
        $koramangala = Gym::where('slug', 'ironforge-koramangala')->firstOrFail();

        // Already seeded? The expiring-membership demo account is created last-but-one; stop here
        // so running the seeder again never piles up duplicate history.
        if (User::where('email', 'expiring@gym.test')->whereHas('memberships')->exists()) {
            $this->command->warn('Testing data is already there; nothing to add. (Run `php artisan migrate:fresh --seed` then this seeder for a clean slate.)');

            return;
        }
        $owner = User::where('email', 'admin@gym.test')->firstOrFail();
        $andheri->forceFill(['owner_id' => $owner->id, 'settings' => array_merge($andheri->settings ?? [], [
            'opening_hours' => 'Mon–Sat 5:30 am – 10:30 pm · Sun 7 am – 12 pm',
            'about' => 'Two floors of free weights, a turf lane for sled work and a dedicated recovery zone. Coaches on the floor all day.',
        ])])->save();

        $korOwner = $this->user('koramangala.admin@gym.test', 'Sunil Kumar', Roles::GYM_ADMIN, $koramangala);
        $koramangala->forceFill(['owner_id' => $korOwner->id])->save();
        $this->user('neha.trainer@gym.test', 'Neha Kulkarni', Roles::TRAINER, $koramangala);
        TrainerProfile::updateOrCreate(['user_id' => User::where('email', 'neha.trainer@gym.test')->value('id')], [
            'specialty' => 'Functional training', 'bio' => 'CrossFit L2 coach who loves getting beginners moving well.', 'experience_years' => 5, 'certifications' => 'CF-L2', 'is_public' => true,
        ]);

        $this->localisePeople($andheri);
        $this->membersFor($koramangala, 12);

        $this->memberships($andheri, $owner);
        $this->memberships($koramangala, $korOwner);
        $this->attendance($andheri);
        $this->attendance($koramangala);
        $this->assignTrainers($andheri);
        $this->plans($andheri);
        $this->weights($andheri);
        $this->enquiries($andheri, $owner);
        $this->enquiries($koramangala, $korOwner);
        $this->joinVisits($andheri);
        $this->joinVisits($koramangala);
        $this->platform($andheri, $koramangala);

        // Member and owner notifications for expiring / unpaid memberships.
        Artisan::call('memberships:remind');

        $this->command->info('Testing data ready. Logins (password ' . DemoDataSeeder::PASSWORD . '):');
        $this->command->table(['Portal', 'Email'], [
            ['Super Admin', 'superadmin@gym.test'],
            ['Gym owner (IronForge Andheri)', 'admin@gym.test'],
            ['Gym owner (IronForge Koramangala)', 'koramangala.admin@gym.test'],
            ['Trainer', 'trainer@gym.test'],
            ['Member (full history + AI scans)', 'member@gym.test'],
            ['Member (membership ending soon)', 'expiring@gym.test'],
        ]);
    }

    /* ---------- People ---------- */

    private function user(string $email, string $name, string $role, ?Gym $gym): User
    {
        $user = User::firstOrCreate(['email' => $email], [
            'name' => $name, 'gym_id' => optional($gym)->id, 'phone' => $this->phone(),
            'password' => Hash::make(DemoDataSeeder::PASSWORD), 'email_verified_at' => now(),
        ]);
        $user->syncRoles([$role]);

        return $user;
    }

    /**
     * Demo members only (seeded @example.* / @gym.test accounts), so real people who signed up
     * on this local copy are never renamed, assigned or given fake history.
     */
    private function demo(?Gym $gym)
    {
        return User::role(Roles::MEMBER)
            ->when($gym, function ($q) use ($gym) { $q->where('users.gym_id', $gym->id); })
            ->where(function ($q) {
                $q->where('users.email', 'like', '%@example.%')->orWhere('users.email', 'like', '%@gym.test');
            });
    }

    private function phone(): string
    {
        do {
            $phone = '+9197' . str_pad((string) (10000000 + (++$this->phoneSeq) * 7919 % 89999999), 8, '0', STR_PAD_LEFT);
        } while (User::where('phone', $phone)->exists());

        return $phone;
    }

    /** Gives the factory-made demo members Indian names, phones and full profiles. */
    private function localisePeople(Gym $gym): void
    {
        $members = $this->demo($gym)->with('memberProfile')->get();
        $sources = ['qr', 'qr', 'qr', 'link', 'manual', 'manual', 'import'];

        foreach ($members as $i => $m) {
            if ($m->email === 'member@gym.test') {
                continue;
            }
            if (! $m->phone) {
                $first = self::FIRST[$i % count(self::FIRST)];
                $last = self::LAST[($i * 7) % count(self::LAST)];
                $m->forceFill(['name' => "$first $last", 'phone' => $this->phone()])->save();
            }
            $this->fillProfile($m, $gym, $sources[$i % count($sources)]);
        }

        // A known member whose membership ends in 3 days, for testing reminders and renewals.
        $exp = $this->user('expiring@gym.test', 'Rohan Kapoor', Roles::MEMBER, $gym);
        $this->fillProfile($exp, $gym, 'qr');
    }

    private function membersFor(Gym $gym, int $count): void
    {
        if ($this->demo($gym)->count() >= $count) {
            return;
        }
        for ($i = 0; $i < $count; $i++) {
            $first = self::FIRST[($i + 11) % count(self::FIRST)];
            $last = self::LAST[($i * 3 + 5) % count(self::LAST)];
            $u = $this->user(strtolower($first . '.' . $last . '.' . $gym->id) . '@example.test', "$first $last", Roles::MEMBER, $gym);
            $u->forceFill(['created_at' => now()->subDays(mt_rand(5, 160))])->save();
            $this->fillProfile($u, $gym, ['qr', 'link', 'manual'][$i % 3]);
        }
    }

    private function fillProfile(User $m, Gym $gym, string $source): void
    {
        $gender = in_array(explode(' ', $m->name)[0], ['Riya', 'Ananya', 'Diya', 'Meera', 'Sneha', 'Neha', 'Pooja', 'Sanya', 'Tanvi', 'Isha', 'Simran', 'Aisha', 'Kritika', 'Nidhi', 'Priya'], true) ? 'female' : 'male';
        $data = [
            'source' => $source,
            'gender' => $gender,
            'date_of_birth' => now()->subYears(mt_rand(19, 45))->subDays(mt_rand(0, 360))->toDateString(),
            'height_cm' => $gender === 'female' ? mt_rand(152, 172) : mt_rand(165, 188),
            'starting_weight_kg' => $gender === 'female' ? mt_rand(52, 82) : mt_rand(64, 98),
            'fitness_goal' => array_keys(MemberProfile::GOALS)[mt_rand(0, count(MemberProfile::GOALS) - 1)],
            'emergency_contact_name' => self::FIRST[mt_rand(0, count(self::FIRST) - 1)] . ' ' . explode(' ', $m->name . ' X')[1],
            'emergency_contact_phone' => '+9199' . mt_rand(10000000, 99999999),
        ];
        $profile = $m->memberProfile;
        if ($profile) {
            $profile->fill(array_filter($data, function ($v, $k) use ($profile) { return $k === 'source' ? $profile->source === 'link' : $profile->{$k} === null; }, ARRAY_FILTER_USE_BOTH))->save();
        } else {
            $m->memberProfile()->create($data + ['member_code' => MemberProfile::nextCode($gym)]);
        }
    }

    /* ---------- Memberships & payments ---------- */

    private function memberships(Gym $gym, User $owner): void
    {
        $plans = MembershipPlan::availableAt($gym->id)->get()->keyBy('slug');
        $monthly = $plans['monthly'];
        $quarterly = $plans['quarterly'];
        $half = $plans['half-yearly'];
        $methods = ['razorpay', 'razorpay', 'upi', 'upi', 'cash', 'card'];

        $members = $this->demo($gym)->where('email', '!=', 'member@gym.test')
            ->whereDoesntHave('memberships')->orderBy('id')->get();

        foreach ($members as $i => $m) {
            if ($m->email === 'expiring@gym.test') {
                $this->paidMembership($m, $gym, $monthly, today()->subDays(27), 'upi', $owner);

                continue;
            }

            switch ($i % 9) {
                case 0: case 1: case 2: // long-standing: an older expired membership, then a current one
                    $first = $this->paidMembership($m, $gym, $quarterly, today()->subDays(mt_rand(100, 150)), $methods[$i % 6], $owner);
                    $this->paidMembership($m, $gym, $monthly, $first->ends_on->copy()->addDay(), $methods[($i + 1) % 6], $owner);
                    break;
                case 3: // ends within the week
                    $this->paidMembership($m, $gym, $monthly, today()->subDays(30 - mt_rand(1, 6)), $methods[$i % 6], $owner);
                    break;
                case 4: case 5: // active half-yearly
                    $this->paidMembership($m, $gym, $half, today()->subDays(mt_rand(10, 120)), $methods[$i % 6], $owner);
                    break;
                case 6: // expired recently, not renewed
                    $this->paidMembership($m, $gym, $monthly, today()->subDays(mt_rand(35, 70)), 'cash', $owner);
                    break;
                case 7: // requested in the app, not paid yet
                    $p = Membership::create(['user_id' => $m->id, 'gym_id' => $gym->id, 'membership_plan_id' => $quarterly->id, 'status' => 'pending', 'amount' => $quarterly->price]);
                    $p->forceFill(['created_at' => now()->subDays(mt_rand(1, 4))])->save();
                    break;
                default: // registered, no membership yet
                    break;
            }
        }
    }

    private function paidMembership(User $m, Gym $gym, MembershipPlan $plan, Carbon $start, string $method, User $owner): Membership
    {
        $end = $start->copy()->addMonthsNoOverflow($plan->duration_months)->subDay();
        $membership = Membership::create([
            'user_id' => $m->id, 'gym_id' => $gym->id, 'membership_plan_id' => $plan->id,
            'status' => $end->lt(today()) ? 'expired' : 'active', 'amount' => $plan->price,
            'starts_on' => $start->toDateString(), 'ends_on' => $end->toDateString(),
            'activated_via' => $method === 'razorpay' ? 'payment' : 'admin',
            'reviewed_by' => $method === 'razorpay' ? null : $owner->id, 'reviewed_at' => $method === 'razorpay' ? null : $start,
        ]);
        $membership->forceFill(['created_at' => $start->copy()->subDay()])->save();

        Payment::create([
            'user_id' => $m->id, 'membership_id' => $membership->id, 'gateway' => $method,
            'gateway_order_id' => ($method === 'razorpay' ? 'order_demo_' : 'desk_') . Str::random(14),
            'gateway_payment_id' => $method === 'razorpay' ? 'pay_demo_' . Str::random(14) : null,
            'amount_paise' => (int) round($plan->price * 100), 'currency' => 'INR', 'status' => 'paid',
            'paid_at' => $start->copy()->setTime(mt_rand(7, 20), mt_rand(0, 59)),
            'recorded_by' => $method === 'razorpay' ? null : $owner->id,
            'note' => $method === 'upi' ? 'UPI ref ' . mt_rand(100000, 999999) : null,
        ]);

        return $membership;
    }

    /* ---------- Attendance ---------- */

    private function attendance(Gym $gym): void
    {
        if (Attendance::where('gym_id', $gym->id)->where('method', 'desk')->exists()) {
            return;
        }
        $members = $this->demo($gym)->where('email', '!=', 'member@gym.test')->get();

        $skipped = 0;
        foreach ($members as $m) {
            $active = $m->activeMembership();
            if (! $active) {
                continue;
            }
            // The first two active members stop coming 2 weeks ago, for the "win them back" report.
            $lastDay = $skipped++ < 2 ? 15 : 1;
            $habit = mt_rand(2, 5); // visits per week
            $from = max(0, today()->diffInDays($active->starts_on, false) * -1);
            for ($d = min(60, $from); $d >= $lastDay; $d--) {
                if (mt_rand(1, 7) > $habit) {
                    continue;
                }
                $in = today()->subDays($d)->setTime([6, 7, 7, 8, 18, 19, 19, 20][mt_rand(0, 7)], mt_rand(0, 59));
                Attendance::create(['user_id' => $m->id, 'gym_id' => $gym->id, 'checked_in_at' => $in, 'checked_out_at' => $in->copy()->addMinutes(mt_rand(45, 100)), 'method' => mt_rand(0, 1) ? 'desk' : 'qr']);
            }
        }

        // Today: some finished, some still inside.
        $members->filter(function ($m) { return $m->activeMembership(); })->slice(2)->take(7)->values()->each(function ($m, $i) use ($gym) {
            $in = now()->subMinutes(30 + $i * 40);
            Attendance::create(['user_id' => $m->id, 'gym_id' => $gym->id, 'checked_in_at' => $in, 'checked_out_at' => $i % 2 ? $in->copy()->addMinutes(70) : null, 'method' => 'desk']);
        });
    }

    /* ---------- Coaching ---------- */

    private function assignTrainers(Gym $gym): void
    {
        $trainers = User::role(Roles::TRAINER)->where('gym_id', $gym->id)->orderBy('id')->pluck('id')->all();
        if (! $trainers) {
            return;
        }
        MemberProfile::whereNull('trainer_id')->whereIn('user_id', $this->demo($gym)->select('users.id'))
            ->orderBy('id')->get()->each(function ($p, $i) use ($trainers) {
                if ($i % 5 !== 4) { // leave a few unassigned
                    $p->update(['trainer_id' => $trainers[$i % count($trainers)]]);
                }
            });
    }

    private function plans(Gym $gym): void
    {
        $trainer = User::where('email', 'trainer@gym.test')->first();
        $kavya = User::where('email', 'kavya.trainer@gym.test')->first();
        $members = $this->demo($gym)->where('email', '!=', 'member@gym.test')
            ->whereDoesntHave('workoutPlans')->orderByRaw("users.email = 'expiring@gym.test' DESC")->orderBy('users.id')->take(9)->get();

        $workouts = [
            ['Beginner full body', 'General fitness', [
                ['day' => 'Monday', 'focus' => 'Full body A', 'exercises' => [['name' => 'Goblet squat', 'sets' => 3, 'reps' => '12', 'rest' => '60s'], ['name' => 'Push-up', 'sets' => 3, 'reps' => '8-12', 'rest' => '60s'], ['name' => 'Dumbbell row', 'sets' => 3, 'reps' => '10', 'rest' => '60s']]],
                ['day' => 'Wednesday', 'focus' => 'Full body B', 'exercises' => [['name' => 'Romanian deadlift', 'sets' => 3, 'reps' => '10', 'rest' => '90s'], ['name' => 'Overhead press', 'sets' => 3, 'reps' => '10', 'rest' => '60s'], ['name' => 'Lat pulldown', 'sets' => 3, 'reps' => '12', 'rest' => '60s']]],
                ['day' => 'Friday', 'focus' => 'Conditioning', 'exercises' => [['name' => 'Bike intervals', 'sets' => 8, 'reps' => '30s hard', 'rest' => '60s'], ['name' => 'Farmer carry', 'sets' => 4, 'reps' => '30 m', 'rest' => '60s']]],
            ]],
            ['Push Pull Legs', 'Muscle gain', [
                ['day' => 'Monday', 'focus' => 'Push', 'exercises' => [['name' => 'Bench press', 'sets' => 4, 'reps' => '6-8', 'rest' => '2 min'], ['name' => 'Incline dumbbell press', 'sets' => 3, 'reps' => '10', 'rest' => '90s'], ['name' => 'Cable fly', 'sets' => 3, 'reps' => '15', 'rest' => '60s'], ['name' => 'Triceps pushdown', 'sets' => 3, 'reps' => '12', 'rest' => '60s']]],
                ['day' => 'Tuesday', 'focus' => 'Pull', 'exercises' => [['name' => 'Deadlift', 'sets' => 3, 'reps' => '5', 'rest' => '3 min'], ['name' => 'Pull-up', 'sets' => 4, 'reps' => 'max', 'rest' => '90s'], ['name' => 'Barbell curl', 'sets' => 3, 'reps' => '10', 'rest' => '60s']]],
                ['day' => 'Thursday', 'focus' => 'Legs', 'exercises' => [['name' => 'Back squat', 'sets' => 4, 'reps' => '6-8', 'rest' => '2 min'], ['name' => 'Leg press', 'sets' => 3, 'reps' => '12', 'rest' => '90s'], ['name' => 'Calf raise', 'sets' => 4, 'reps' => '15', 'rest' => '45s']]],
            ]],
        ];
        $diets = [
            ['Lean bulk · 2,800 kcal', 2800, ['protein' => 160, 'carbs' => 340, 'fat' => 85]],
            ['Fat loss · 1,800 kcal', 1800, ['protein' => 130, 'carbs' => 180, 'fat' => 55]],
        ];

        foreach ($members as $i => $m) {
            [$title, $goal, $days] = $workouts[$i % 2];
            $by = $i % 2 ? $kavya : $trainer;
            WorkoutPlan::create(['member_id' => $m->id, 'trainer_id' => optional($by)->id, 'title' => $title, 'goal' => $goal,
                'starts_on' => today()->subDays(mt_rand(3, 30))->toDateString(), 'days' => $days, 'is_active' => true,
                'notes' => 'Warm up for 10 minutes. Log your weights so we can progress them.']);

            if ($i < 6) {
                [$dTitle, $kcal, $macros] = $diets[$i % 2];
                DietPlan::create(['member_id' => $m->id, 'trainer_id' => optional($by)->id, 'title' => $dTitle, 'daily_calories' => $kcal, 'macros' => $macros, 'is_active' => true,
                    'notes' => 'Drink 3 litres of water a day.',
                    'meals' => [
                        ['time' => '7:30 am', 'name' => 'Breakfast', 'items' => ['Oats with milk and banana', '3 boiled eggs or paneer bhurji']],
                        ['time' => '1:30 pm', 'name' => 'Lunch', 'items' => ['2 rotis', 'Dal', 'Chicken curry or rajma', 'Salad']],
                        ['time' => '5:00 pm', 'name' => 'Snack', 'items' => ['Sprouts chaat', 'Buttermilk']],
                        ['time' => '8:30 pm', 'name' => 'Dinner', 'items' => ['Grilled fish or tofu', 'Brown rice', 'Sautéed vegetables']],
                    ]]);
            }
        }
    }

    private function weights(Gym $gym): void
    {
        $this->demo($gym)->where('email', '!=', 'member@gym.test')
            ->whereDoesntHave('weightLogs')->with('memberProfile')->take(8)->get()->each(function ($m) {
                $start = (float) (optional($m->memberProfile)->starting_weight_kg ?: 75);
                for ($w = 8; $w >= 0; $w--) {
                    WeightLog::create(['user_id' => $m->id, 'logged_on' => today()->subWeeks($w)->toDateString(), 'weight_kg' => round($start - (8 - $w) * 0.4 + mt_rand(-4, 4) / 10, 1)]);
                }
            });
    }

    /* ---------- Enquiries & QR ---------- */

    private function enquiries(Gym $gym, User $owner): void
    {
        if (Enquiry::where('gym_id', $gym->id)->exists()) {
            return;
        }
        $staff = User::role([Roles::GYM_ADMIN, Roles::TRAINER])->where('gym_id', $gym->id)->pluck('id')->all();
        $rows = [
            ['Aman Bhatia', 'walk_in', 'Quarterly', 'new', 0, 'Asked about morning batch timings.'],
            ['Shruti Menon', 'instagram', 'Personal training', 'follow_up', 0, 'Saw our transformation reel. Wants PT 3x a week.'],
            ['Faizan Qureshi', 'phone', 'Monthly', 'follow_up', -2, 'Called twice, will visit after Diwali.'],
            ['Lavanya Rao', 'website', 'Half-Yearly', 'trial', 1, 'Trial booked for Saturday 7 am.'],
            ['Gaurav Sethi', 'referral', 'Annual', 'trial', -1, 'Referred by Rahul. Wants couple discount.'],
            ['Mitali Das', 'facebook', 'Student', 'new', 2, 'College student, needs off-peak plan.'],
            ['Yash Thakur', 'walk_in', 'Quarterly', 'follow_up', 3, 'Comparing with another gym nearby.'],
            ['Payal Jain', 'instagram', 'Monthly', 'lost', null, 'Too far from home.'],
            ['Deepak Yadav', 'phone', 'Annual', 'lost', null, 'Price.'],
            ['Kunal Arora', 'walk_in', 'Monthly', 'new', 5, null],
        ];

        foreach ($rows as $i => [$name, $source, $interest, $status, $followIn, $note]) {
            $created = now()->subDays(mt_rand(1, 75));
            $e = Enquiry::create([
                'gym_id' => $gym->id, 'name' => $name, 'phone' => '+9196' . mt_rand(10000000, 99999999),
                'email' => $i % 3 === 0 ? Str::slug($name, '.') . '@example.test' : null,
                'source' => $source, 'interest' => $interest, 'status' => $status,
                'next_follow_up_on' => $followIn === null ? null : today()->addDays($followIn)->toDateString(),
                'notes' => $note, 'assigned_to' => $staff ? $staff[$i % count($staff)] : null,
                'lost_reason' => $status === 'lost' ? $note : null,
            ]);
            $e->forceFill(['created_at' => $created, 'updated_at' => $created])->save();
            $e->activities()->forceCreate(['user_id' => $owner->id, 'note' => 'Enquiry added (' . Enquiry::SOURCES[$source] . ')', 'created_at' => $created]);
            if ($status !== 'new') {
                $e->activities()->forceCreate(['user_id' => $owner->id, 'note' => $note ?: 'Followed up by phone', 'created_at' => $created->copy()->addDays(2)]);
            }
        }

        // Converted enquiries, linked to real members (for conversion tracking).
        $this->demo($gym)->whereHas('memberships')->where('email', '!=', 'member@gym.test')
            ->orderByDesc('id')->take(5)->get()->each(function ($m, $i) use ($gym, $owner) {
                $created = $m->created_at->copy()->subDays(mt_rand(2, 9));
                $e = Enquiry::create([
                    'gym_id' => $gym->id, 'name' => $m->name, 'phone' => $m->phone ?: '+9195' . mt_rand(10000000, 99999999),
                    'source' => ['walk_in', 'instagram', 'referral', 'website', 'walk_in'][$i], 'interest' => 'Monthly', 'status' => 'converted',
                    'converted_user_id' => $m->id, 'converted_at' => $m->created_at,
                ]);
                $e->forceFill(['created_at' => $created])->save();
                $e->activities()->forceCreate(['user_id' => $owner->id, 'note' => 'Joined as member ' . optional($m->memberProfile)->member_code, 'created_at' => $m->created_at]);
            });
    }

    private function joinVisits(Gym $gym): void
    {
        if (JoinVisit::where('gym_id', $gym->id)->exists()) {
            return;
        }
        for ($d = 150; $d >= 0; $d--) {
            foreach (['qr' => mt_rand(0, 3), 'link' => mt_rand(0, 1)] as $source => $n) {
                for ($k = 0; $k < $n; $k++) {
                    JoinVisit::forceCreate(['gym_id' => $gym->id, 'source' => $source, 'created_at' => now()->subDays($d)->setTime(mt_rand(6, 21), mt_rand(0, 59))]);
                }
            }
        }
    }

    /* ---------- FlexFit platform (Super Admin) ---------- */

    private function platform(Gym $andheri, Gym $koramangala): void
    {
        $growth = SubscriptionPlan::where('slug', 'growth')->first();
        $starter = SubscriptionPlan::where('slug', 'starter')->first();
        $super = User::where('email', 'superadmin@gym.test')->first();

        if ($growth && ! $andheri->subscription()->exists()) {
            $sub = GymSubscription::create(['gym_id' => $andheri->id, 'subscription_plan_id' => $growth->id, 'status' => 'active', 'billing_cycle' => 'monthly',
                'amount' => $growth->price_monthly, 'starts_on' => today()->subMonths(5)->toDateString(), 'ends_on' => today()->addDays(12)->toDateString()]);
            for ($mo = 5; $mo >= 0; $mo--) {
                $from = today()->subMonths($mo)->subDays(18);
                SubscriptionPayment::create(['gym_id' => $andheri->id, 'gym_subscription_id' => $sub->id, 'amount' => $growth->price_monthly,
                    'method' => $mo % 2 ? 'upi' : 'bank', 'reference' => 'UTR' . mt_rand(10000000, 99999999), 'status' => 'paid',
                    'period_start' => $from, 'period_end' => $from->copy()->addMonth(), 'paid_at' => $from->copy()->setTime(11, 0), 'recorded_by' => optional($super)->id]);
            }
        }
        if ($starter && ! $koramangala->subscription()->exists()) {
            $sub = GymSubscription::create(['gym_id' => $koramangala->id, 'subscription_plan_id' => $starter->id, 'status' => 'past_due', 'billing_cycle' => 'monthly',
                'amount' => $starter->price_monthly, 'starts_on' => today()->subMonths(3)->toDateString(), 'ends_on' => today()->subDays(4)->toDateString()]);
            foreach ([3, 2, 1] as $mo) {
                $from = today()->subMonths($mo)->subDays(4);
                SubscriptionPayment::create(['gym_id' => $koramangala->id, 'gym_subscription_id' => $sub->id, 'amount' => $starter->price_monthly,
                    'method' => 'upi', 'status' => 'paid', 'period_start' => $from, 'period_end' => $from->copy()->addMonth(), 'paid_at' => $from->copy()->setTime(15, 30), 'recorded_by' => optional($super)->id]);
            }
        }

        if (! AiUsageLog::exists()) {
            $members = $this->demo(null)->whereIn('gym_id', [$andheri->id, $koramangala->id])->get(['id', 'gym_id']);
            for ($d = 120; $d >= 0; $d--) {
                for ($k = 0, $n = mt_rand(0, 2); $k < $n; $k++) {
                    $m = $members->random();
                    $compare = mt_rand(1, 4) === 1;
                    $in = $compare ? mt_rand(9000, 12000) : mt_rand(5000, 7000);
                    $out = mt_rand(1500, 3200);
                    $ok = mt_rand(1, 20) > 1;
                    AiUsageLog::forceCreate([
                        'gym_id' => $m->gym_id, 'user_id' => $m->id, 'kind' => $compare ? 'scan_comparison' : 'scan_analysis', 'model' => 'claude-opus-5-5',
                        'input_tokens' => $in, 'output_tokens' => $ok ? $out : 0, 'cost_usd' => AiUsageLog::estimateCost('claude-opus-5-5', $in, $ok ? $out : 0),
                        'success' => $ok, 'error' => $ok ? null : 'These photos could not be analysed. Please retake them in fitted gym clothing with good lighting.',
                        'created_at' => now()->subDays($d)->setTime(mt_rand(6, 22), mt_rand(0, 59)),
                    ]);
                }
            }
        }

        if (! SupportRequest::exists()) {
            $andOwner = User::where('email', 'admin@gym.test')->first();
            $korOwner = User::where('email', 'koramangala.admin@gym.test')->first();
            foreach ([
                [$andheri, $andOwner, 'QR poster prints too small', 'technical', 'normal', 'open', null],
                [$andheri, $andOwner, 'Need GST invoice for last 3 months', 'billing', 'high', 'in_progress', 'Sure — we are generating them and will email by Friday.'],
                [$koramangala, $korOwner, 'Subscription payment failed', 'billing', 'urgent', 'open', null],
                [$koramangala, $korOwner, 'Can members book classes?', 'feature', 'low', 'resolved', 'Class booking is on our roadmap for next quarter. We\'ll let you know!'],
            ] as [$g, $u, $subject, $cat, $prio, $status, $reply]) {
                SupportRequest::create(['gym_id' => $g->id, 'user_id' => optional($u)->id, 'subject' => $subject, 'message' => $subject . '. Please help, thanks!',
                    'category' => $cat, 'priority' => $prio, 'status' => $status, 'admin_reply' => $reply,
                    'replied_at' => $reply ? now()->subDays(1) : null, 'resolved_at' => $status === 'resolved' ? now()->subDays(1) : null]);
            }
        }

        if (! DemoRequest::exists()) {
            foreach ([
                ['Vikas Gupta', 'Muscle Factory', 'Pune', '100-300', 'new'],
                ['Ritu Saxena', 'FitZone Ladies Gym', 'Lucknow', '<100', 'contacted'],
                ['Arvind Pillai', 'Pulse Fitness', 'Kochi', '300-700', 'scheduled'],
                ['Manpreet Kaur', 'Iron Temple', 'Chandigarh', 'multi', 'converted'],
                ['Sameer Khan', 'Beast Mode Gym', 'Hyderabad', '100-300', 'new'],
            ] as $i => [$name, $gymName, $city, $size, $status]) {
                $d = DemoRequest::create(['name' => $name, 'email' => Str::slug($name, '.') . '@example.test', 'phone' => '+9193' . mt_rand(10000000, 99999999),
                    'gym_name' => $gymName, 'city' => $city, 'members' => $size, 'preferred_date' => today()->addDays($i + 1)->toDateString(),
                    'preferred_time' => ['morning', 'afternoon', 'evening'][$i % 3], 'message' => $i % 2 ? 'Want to see the AI body scan feature.' : null, 'status' => $status]);
                $d->forceFill(['created_at' => now()->subDays($i * 3 + 1)])->save();
            }
        }
    }
}
