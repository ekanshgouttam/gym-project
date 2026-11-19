<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\BodyMeasurement;
use App\Models\DietPlan;
use App\Models\Gym;
use App\Models\MemberProfile;
use App\Models\Membership;
use App\Models\MembershipPlan;
use App\Models\TrainerProfile;
use App\Models\User;
use App\Models\WeightLog;
use App\Models\WorkoutPlan;
use App\Notifications\WelcomeMember;
use App\Support\Roles;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Local demo data. Never run in production.
 * All demo accounts use the password: Password@123
 */
class DemoDataSeeder extends Seeder
{
    public const PASSWORD = 'Password@123';

    public function run()
    {
        $gym = Gym::firstOrCreate(['slug' => 'ironforge-andheri'], [
            'name' => 'IronForge Andheri', 'email' => 'andheri@ironforge.test', 'phone' => '+91 98200 00001',
            'address' => 'Link Road, Andheri West', 'city' => 'Mumbai',
        ]);
        $gym2 = Gym::firstOrCreate(['slug' => 'ironforge-koramangala'], [
            'name' => 'IronForge Koramangala', 'email' => 'koramangala@ironforge.test', 'phone' => '+91 98200 00002',
            'address' => '80 Feet Road, Koramangala', 'city' => 'Bengaluru',
        ]);
        foreach ([$gym, $gym2] as $g) {
            if (! $g->join_token) {
                $g->forceFill(['join_token' => Gym::newJoinToken()])->save();
            }
        }

        $this->plans($gym);

        $accounts = [
            ['superadmin@gym.test', 'Super Admin', Roles::SUPER_ADMIN, null, null],
            ['admin@gym.test', 'Gym Admin', Roles::GYM_ADMIN, $gym->id, '+919820000010'],
            ['trainer@gym.test', 'Arjun Mehta', Roles::TRAINER, $gym->id, '+919820000011'],
            ['kavya.trainer@gym.test', 'Kavya Iyer', Roles::TRAINER, $gym->id, '+919820000012'],
            ['rohan.trainer@gym.test', 'Rohan Das', Roles::TRAINER, $gym2->id, '+919820000013'],
            ['member@gym.test', 'Priya Sharma', Roles::MEMBER, $gym->id, '+919820000020'],
        ];

        $users = [];
        foreach ($accounts as [$email, $name, $role, $gymId, $phone]) {
            $user = User::firstOrCreate(['email' => $email], [
                'name' => $name, 'gym_id' => $gymId, 'phone' => $phone,
                'password' => Hash::make(self::PASSWORD), 'email_verified_at' => now(),
            ]);
            $user->syncRoles([$role]);
            $users[$email] = $user;
        }

        $trainers = [
            'trainer@gym.test' => ['Strength & hypertrophy', 'Former national-level powerlifter who loves teaching beginners to squat, bench and deadlift safely.', 8, 'ACE CPT · Precision Nutrition L1'],
            'kavya.trainer@gym.test' => ['Fat loss & conditioning', 'Builds sustainable fat-loss programmes that fit around busy work weeks.', 6, 'ACSM EP-C · Yoga Alliance RYT-200'],
            'rohan.trainer@gym.test' => ['Mobility & rehab', 'Physiotherapy background, specialising in returning clients to training after injury.', 10, 'BPT · FRC Mobility Specialist'],
        ];
        foreach ($trainers as $email => [$specialty, $bio, $years, $certs]) {
            TrainerProfile::updateOrCreate(['user_id' => $users[$email]->id], [
                'specialty' => $specialty, 'bio' => $bio, 'experience_years' => $years, 'certifications' => $certs, 'is_public' => true,
            ]);
        }

        $this->demoMember($users['member@gym.test'], $gym, $users['trainer@gym.test']);

        // Extra members so admin dashboards and charts have data.
        if (User::where('gym_id', $gym->id)->count() < 10) {
            User::factory()->count(18)->state(function () use ($gym) {
                return ['gym_id' => $gym->id, 'created_at' => now()->subDays(random_int(0, 170))];
            })->create()->each(function (User $u) use ($gym) {
                $u->assignRole(Roles::MEMBER);
                $u->memberProfile()->create(['member_code' => MemberProfile::nextCode($gym), 'fitness_goal' => array_rand(MemberProfile::GOALS)]);
            });
        }
    }

    private function plans(Gym $gym): void
    {
        $plans = [
            ['Monthly', 1, 2499, 'Flexible month-to-month access.', ['Full gym floor access', 'Group classes', 'Member portal & tracking', '1 AI body scan'], false, 1],
            ['Quarterly', 3, 6499, 'Three months to build the habit.', ['Everything in Monthly', 'Trainer-built workout plan', 'Monthly AI body scan', '1 personal training session'], false, 2],
            ['Half-Yearly', 6, 11999, 'Our most popular commitment.', ['Everything in Quarterly', 'Personalised diet plan', 'Monthly trainer check-in', '2 guest passes'], true, 3],
            ['Annual', 12, 19999, 'Best value for serious transformation.', ['Everything in Half-Yearly', '4 personal training sessions', 'Priority class booking', 'Freeze up to 30 days'], false, 4],
        ];
        foreach ($plans as [$name, $months, $price, $desc, $features, $featured, $sort]) {
            MembershipPlan::updateOrCreate(['slug' => strtolower($name), 'gym_id' => null], [
                'name' => $name, 'duration_months' => $months, 'price' => $price, 'description' => $desc,
                'features' => $features, 'is_featured' => $featured, 'is_active' => true, 'sort_order' => $sort,
            ]);
        }
        MembershipPlan::updateOrCreate(['slug' => 'student-andheri', 'gym_id' => $gym->id], [
            'name' => 'Student', 'duration_months' => 3, 'price' => 4999, 'description' => 'Valid college ID required at the front desk.',
            'features' => ['Off-peak access (before 5 pm)', 'Group classes', 'Monthly AI body scan'], 'is_featured' => false, 'is_active' => true, 'sort_order' => 5,
        ]);
    }

    private function demoMember(User $member, Gym $gym, User $trainer): void
    {
        if ($member->memberProfile) {
            return; // already seeded
        }

        $joined = now()->subDays(75)->startOfDay();
        $member->forceFill(['created_at' => $joined])->save();

        $profile = $member->memberProfile()->create([
            'member_code' => MemberProfile::nextCode($gym),
            'date_of_birth' => now()->subYears(29)->subDays(40)->toDateString(),
            'gender' => 'female', 'height_cm' => 164, 'starting_weight_kg' => 71.4, 'target_weight_kg' => 63,
            'fitness_goal' => 'fat_loss', 'emergency_contact_name' => 'Rahul Sharma', 'emergency_contact_phone' => '+919820000099',
            'phone_verified_at' => $joined,
        ]);

        $quarterly = MembershipPlan::where('slug', 'quarterly')->firstOrFail();
        Membership::create([
            'user_id' => $member->id, 'gym_id' => $gym->id, 'membership_plan_id' => $quarterly->id, 'status' => 'active',
            'amount' => $quarterly->price, 'starts_on' => $joined->toDateString(),
            'ends_on' => $joined->copy()->addMonthsNoOverflow(3)->subDay()->toDateString(), 'activated_via' => 'payment',
        ]);

        // Weekly weigh-ins trending down with some noise.
        for ($w = 0; $w <= 10; $w++) {
            WeightLog::create([
                'user_id' => $member->id, 'logged_on' => $joined->copy()->addWeeks($w)->toDateString(),
                'weight_kg' => round(71.4 - $w * 0.55 + (mt_rand(-3, 3) / 10), 1), 'note' => $w === 0 ? 'Joining weight' : null,
            ]);
        }

        foreach ([0, 4, 8] as $i => $week) {
            BodyMeasurement::create([
                'user_id' => $member->id, 'measured_on' => $joined->copy()->addWeeks($week)->toDateString(),
                'chest_cm' => 92 - $i * 1.2, 'waist_cm' => 82 - $i * 2.4, 'hips_cm' => 101 - $i * 1.6,
                'arms_cm' => 30 - $i * 0.3, 'thighs_cm' => 58 - $i * 1.0, 'neck_cm' => 33 - $i * 0.2,
            ]);
        }

        // ~4 visits a week.
        for ($d = 75; $d >= 1; $d--) {
            $day = now()->subDays($d);
            if (in_array($day->dayOfWeekIso, [1, 2, 4, 6], true) && mt_rand(1, 10) > 1) {
                $in = $day->copy()->setTime(mt_rand(6, 8), mt_rand(0, 59));
                Attendance::create(['user_id' => $member->id, 'gym_id' => $gym->id, 'checked_in_at' => $in, 'checked_out_at' => $in->copy()->addMinutes(mt_rand(55, 95)), 'method' => 'qr']);
            }
        }

        WorkoutPlan::create([
            'member_id' => $member->id, 'trainer_id' => $trainer->id, 'title' => 'Fat-loss strength block', 'goal' => 'Fat loss while keeping strength',
            'starts_on' => now()->subDays(20)->toDateString(), 'ends_on' => now()->addDays(36)->toDateString(),
            'notes' => 'Keep 1–2 reps in reserve on every set. Add 2.5 kg when you hit the top of the rep range on all sets.',
            'days' => [
                ['day' => 'Monday', 'focus' => 'Lower body strength', 'exercises' => [
                    ['name' => 'Back squat', 'sets' => 4, 'reps' => '6-8', 'rest' => '2 min'], ['name' => 'Romanian deadlift', 'sets' => 3, 'reps' => '8-10', 'rest' => '90 s'],
                    ['name' => 'Walking lunge', 'sets' => 3, 'reps' => '12/leg', 'rest' => '60 s'], ['name' => 'Plank', 'sets' => 3, 'reps' => '45 s', 'rest' => '30 s']]],
                ['day' => 'Tuesday', 'focus' => 'Upper body push/pull', 'exercises' => [
                    ['name' => 'Dumbbell bench press', 'sets' => 4, 'reps' => '8-10', 'rest' => '90 s'], ['name' => 'Lat pulldown', 'sets' => 4, 'reps' => '10-12', 'rest' => '90 s'],
                    ['name' => 'Seated cable row', 'sets' => 3, 'reps' => '10-12', 'rest' => '60 s'], ['name' => 'Lateral raise', 'sets' => 3, 'reps' => '15', 'rest' => '45 s']]],
                ['day' => 'Thursday', 'focus' => 'Conditioning circuit', 'exercises' => [
                    ['name' => 'Rower intervals', 'sets' => 6, 'reps' => '250 m', 'rest' => '60 s'], ['name' => 'Kettlebell swing', 'sets' => 4, 'reps' => '15', 'rest' => '45 s'],
                    ['name' => 'Sled push', 'sets' => 4, 'reps' => '20 m', 'rest' => '60 s']]],
                ['day' => 'Saturday', 'focus' => 'Full body', 'exercises' => [
                    ['name' => 'Trap-bar deadlift', 'sets' => 4, 'reps' => '5', 'rest' => '2 min'], ['name' => 'Incline dumbbell press', 'sets' => 3, 'reps' => '10', 'rest' => '90 s'],
                    ['name' => 'Goblet squat', 'sets' => 3, 'reps' => '12', 'rest' => '60 s'], ['name' => 'Farmer carry', 'sets' => 3, 'reps' => '40 m', 'rest' => '60 s']]],
            ],
        ]);

        DietPlan::create([
            'member_id' => $member->id, 'trainer_id' => $trainer->id, 'title' => 'Moderate deficit · high protein',
            'daily_calories' => 1650, 'macros' => ['protein' => 120, 'carbs' => 170, 'fat' => 55],
            'notes' => 'Aim for 2.5–3 L of water daily. Swap meals freely as long as you hit the protein target.',
            'meals' => [
                ['time' => '7:30 am', 'name' => 'Breakfast', 'items' => ['Moong dal chilla (2) with paneer filling', 'Green chutney', 'Black coffee or tea, no sugar']],
                ['time' => '11:00 am', 'name' => 'Mid-morning', 'items' => ['Greek yogurt (150 g)', '1 apple']],
                ['time' => '1:30 pm', 'name' => 'Lunch', 'items' => ['2 multigrain rotis', 'Grilled chicken or tofu (150 g)', 'Mixed vegetable sabzi', 'Salad']],
                ['time' => '5:00 pm', 'name' => 'Pre-workout', 'items' => ['Banana', 'Handful of roasted chana']],
                ['time' => '8:30 pm', 'name' => 'Dinner', 'items' => ['Dal (1 bowl)', 'Brown rice (½ cup cooked)', 'Sautéed greens', 'Whey shake if protein is short']],
            ],
        ]);

        $member->notify(new WelcomeMember($profile->member_code));

        $this->demoScans($member, $joined);
    }

    /**
     * Three monthly scans (baseline, +31 d, +62 d) with generated placeholder photos and sample
     * reports clearly marked as demo data, so comparisons and the 30-day countdown can be tried locally.
     */
    private function demoScans(User $member, \Illuminate\Support\Carbon $joined): void
    {
        $inspector = new \App\Services\Vision\PhotoQualityInspector();
        $samples = [
            [0, 71.4, 52, 'Baseline captured. Upright stance with shoulders rolling slightly forward; softer midsection. A good, clear starting point.'],
            [31, 69.1, 58, 'Shoulders sit a little further back than at baseline and the waistline looks slightly narrower from the front.'],
            [62, 66.9, 64, 'Clear visual progress since baseline: a more defined waist from the front and side, and more upright posture.'],
        ];

        foreach ($samples as $i => [$day, $weight, $score, $summary]) {
            $at = $joined->copy()->addDays($day)->setTime(7, 30);
            $paths = [];
            $originals = [];
            $quality = [];
            foreach (\App\Models\BodyScan::VIEWS as $view) {
                $img = $this->demoPhoto($view, $i);
                $quality[$view] = $inspector->inspect($img, $view);
                ob_start();
                imagejpeg($img, null, 85);
                $bytes = ob_get_clean();
                imagedestroy($img);

                $display = "scans/{$member->id}/demo-{$i}-{$view}.jpg";
                $original = "scans/{$member->id}/originals/demo-{$i}-{$view}.jpg";
                \Illuminate\Support\Facades\Storage::disk('local')->put($display, $bytes);
                \Illuminate\Support\Facades\Storage::disk('local')->put($original, $bytes);
                $paths[$view . '_path'] = $display;
                $originals[$view] = ['path' => $original, 'sha256' => hash('sha256', $bytes), 'bytes' => strlen($bytes), 'mime' => 'image/jpeg'];
            }

            $scan = new \App\Models\BodyScan($paths + [
                'user_id' => $member->id,
                'is_baseline' => $i === 0,
                'originals' => $originals,
                'quality' => $quality,
                'weight_kg' => $weight,
                'height_cm' => 164,
                'status' => 'completed',
                'analysis_attempts' => 1,
                'physique_score' => $score,
                'summary' => '[Demo data] ' . $summary,
                'analyzed_at' => $at,
                'analysis' => [
                    'image_quality_ok' => true, 'image_quality_issue' => '',
                    'posture' => ['summary' => $i === 0 ? 'Slight forward shoulder roll.' : 'More upright than at baseline.', 'observations' => ['Head sits slightly forward of the shoulders.', 'Even weight distribution between both feet.']],
                    'proportions' => 'Shoulders and hips appear similar in width; the waist is ' . ['moderately', 'slightly more', 'noticeably more'][$i] . ' defined from the front.',
                    'body_shape' => 'Balanced frame with most visible softness around the midsection.',
                    'visual_leanness' => ['moderate', 'moderate', 'lean'][$i],
                    'visible_definition' => ['upper_body' => ['low', 'moderate', 'moderate'][$i], 'core' => ['low', 'low', 'moderate'][$i], 'lower_body' => 'moderate'],
                    'observations' => ['Visible definition in the shoulders is ' . ['limited', 'emerging', 'clearer'][$i] . '.', 'Legs show good visible tone from the side view.'],
                    'strengths' => ['Consistent training shows in lower-body tone', 'Good overall symmetry'],
                    'focus_areas' => ['Upper-back strength for posture', 'Core work'],
                    'recommendations' => ['Add two sets of face pulls on upper-body days', 'Keep protein near your 120 g target', 'Walk 8,000+ steps daily'],
                    'visual_score' => $score,
                    'changes_since_last' => $i === 0 ? '' : $summary,
                    'photo_consistency_tips' => ['Keep the same distance and wall each month.'],
                    'summary' => '[Demo data] ' . $summary,
                ],
            ]);
            $scan->created_at = $at;
            $scan->updated_at = $at;
            $scan->save();

            \App\Models\ScanAuditLog::forceCreate([
                'member_id' => $member->id, 'body_scan_id' => $scan->id, 'actor_id' => $member->id,
                'action' => $i === 0 ? 'baseline_created' : 'uploaded', 'changes' => ['weight_kg' => $weight], 'created_at' => $at,
            ]);
        }
    }

    /** Simple generated silhouette standing in for a real progress photo. */
    private function demoPhoto(string $view, int $month)
    {
        $w = 600;
        $h = 800;
        $img = imagecreatetruecolor($w, $h);
        for ($y = 0; $y < $h; $y++) { // soft vertical gradient "wall"
            $c = (int) (150 - $y / $h * 40);
            imageline($img, 0, $y, $w, $y, imagecolorallocate($img, $c, $c + 4, $c + 10));
        }
        imagefilledrectangle($img, 0, 690, $w, $h, imagecolorallocate($img, 90, 85, 80)); // floor

        $skin = imagecolorallocate($img, 196, 150, 120);
        $cloth = imagecolorallocate($img, 30, 34, 44);
        $cx = 300;
        $waist = [120, 108, 96][$month];          // narrows month to month
        $side = $view === 'side';
        $torso = $side ? (int) ($waist * 0.7) : $waist;

        imagefilledellipse($img, $cx, 120, 70, 88, $skin);                                   // head
        imagefilledrectangle($img, $cx - 14, 160, $cx + 14, 185, $skin);                    // neck
        imagefilledpolygon($img, [$cx - ($side ? 55 : 95), 190, $cx + ($side ? 55 : 95), 190, $cx + (int) ($torso / 2), 400, $cx - (int) ($torso / 2), 400], 4, $skin); // torso
        imagefilledrectangle($img, $cx - (int) ($torso / 2) - 6, 390, $cx + (int) ($torso / 2) + 6, 470, $cloth); // shorts
        foreach ($side ? [0] : [-38, 38] as $dx) {
            imagefilledrectangle($img, $cx + $dx - 22, 470, $cx + $dx + 22, 690, $skin);  // legs
        }
        if (! $side) {
            imagefilledpolygon($img, [$cx - 95, 195, $cx - 75, 195, $cx - 115, 420, $cx - 135, 420], 4, $skin);    // arms
            imagefilledpolygon($img, [$cx + 95, 195, $cx + 75, 195, $cx + 115, 420, $cx + 135, 420], 4, $skin);
        } else {
            imagefilledrectangle($img, $cx - 12, 200, $cx + 12, 420, $skin);
        }
        if ($view !== 'back') {
            imagefilledrectangle($img, $cx - ($side ? 30 : 60), 215, $cx + ($side ? 30 : 60), 270, $cloth); // top
        }

        $white = imagecolorallocate($img, 255, 255, 255);
        imagestring($img, 5, 16, 16, 'DEMO PHOTO - ' . strtoupper($view), $white);
        imagestring($img, 3, 16, 36, 'Month ' . ($month + 1), $white);

        return $img;
    }
}
