<?php

namespace App\Http\Controllers;

use App\Models\BodyScan;
use App\Models\Enquiry;
use App\Models\Gym;
use App\Models\MembershipPlan;
use App\Models\PlatformSetting;
use App\Models\User;
use App\Services\OtpService;
use App\Support\Roles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * The premium gym website at "/". It represents one gym (Super Admin → Platform settings →
 * Website gym); copy and default media come from config/site.php, everything else from that
 * gym's data. Contact and trial requests become Enquiries in the gym owner's panel.
 */
class SiteController extends Controller
{
    public function home()
    {
        $gym = self::gym();
        abort_unless($gym, 404, 'No gym is set up for the website yet.');

        return view('site.home', [
            'gym' => $gym,
            'site' => self::content($gym),
            'plans' => MembershipPlan::availableAt($gym->id)->get(),
            'trainers' => User::role(Roles::TRAINER)->where('gym_id', $gym->id)->where('status', 'active')
                ->whereHas('trainerProfile', function ($q) { $q->where('is_public', true); })
                ->with('trainerProfile')->orderBy('name')->get(),
            'stats' => $this->stats($gym),
        ]);
    }

    /** Contact form and "book a free trial" form → a new enquiry for the gym. */
    public function enquire(Request $request)
    {
        $gym = self::gym();
        abort_unless($gym, 404);

        // Honeypot: real visitors never fill this hidden field.
        if ($request->filled('company')) {
            $form = $request->input('form') === 'join' ? 'join' : 'contact';

            return redirect()->to(route('home') . '#' . $form)->with('site_form', $form)->with('site_status', 'Thanks! We will be in touch shortly.');
        }

        $form = $request->input('form') === 'join' ? 'join' : 'contact';
        // Errors go back to the form's own section (a plain "back" redirect would drop the #anchor
        // and leave the visitor at the top of a long page).
        $back = function ($errors) use ($form) {
            return redirect()->to(route('home') . '#' . $form)->withErrors($errors, $form)->withInput();
        };

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'interest' => ['nullable', 'string', 'max:120'],
            'message' => ['nullable', 'string', 'max:2000'],
            'preferred_time' => ['nullable', Rule::in(['morning', 'afternoon', 'evening'])],
        ]);
        if ($validator->fails()) {
            return $back($validator);
        }
        $data = $validator->validated();

        $phone = OtpService::normalizePhone($data['phone']);
        if (! $phone) {
            return $back(['phone' => 'Enter a valid 10-digit mobile number.']);
        }

        $note = trim(($form === 'join' ? 'Free trial request' : 'Website contact form')
            . (! empty($data['preferred_time']) ? ' · prefers ' . $data['preferred_time'] : '')
            . (! empty($data['message']) ? "\n" . $data['message'] : ''));

        $enquiry = Enquiry::create([
            'gym_id' => $gym->id, 'name' => $data['name'], 'phone' => $phone, 'email' => $data['email'] ?? null,
            'source' => 'website', 'interest' => $data['interest'] ?? ($form === 'join' ? 'Free trial' : null),
            'status' => $form === 'join' ? 'trial' : 'new', 'next_follow_up_on' => today(), 'notes' => $note,
        ]);
        $enquiry->log(null, $form === 'join' ? 'Booked a free trial on the website' : 'Sent a message from the website');

        $first = explode(' ', trim($data['name']))[0];

        return redirect()->to(route('home') . '#' . $form)->with('site_form', $form)->with('site_status', $form === 'join'
            ? "You're booked, {$first}! We'll call you today to fix your free trial session."
            : "Thanks, {$first}! The team will get back to you shortly.");
    }

    /** The gym the website represents. */
    public static function gym(): ?Gym
    {
        $id = PlatformSetting::get('site_gym');

        return ($id ? Gym::where('id', $id)->where('is_active', true)->first() : null)
            ?: Gym::where('is_active', true)->orderBy('id')->first();
    }

    /** config/site.php, with media the gym owner uploaded taking priority. */
    public static function content(Gym $gym): array
    {
        $site = config('site');
        $media = $gym->setting('site_media') ?: [];
        if (! empty($media['hero_video'])) {
            $site['hero']['video'] = $site['hero']['video_mobile'] = route('site.media', [$gym, $media['hero_video']], false);
        }
        if (! empty($media['hero_image'])) {
            $site['hero']['poster'] = $site['hero']['poster_mobile'] = route('site.media', [$gym, $media['hero_image']], false);
        }
        if (! empty($media['gallery'])) {
            $site['gallery'] = collect($media['gallery'])->values()->map(function ($file, $i) use ($gym) {
                return ['type' => 'image', 'src' => route('site.media', [$gym, $file], false), 'alt' => $gym->name . ' gallery photo ' . ($i + 1), 'size' => $i % 3 === 1 ? 'tall' : 'wide'];
            })->concat($site['gallery'])->take(9)->all();
        }

        return $site;
    }

    private function stats(Gym $gym): array
    {
        return Cache::remember("site.stats.{$gym->id}", 600, function () use ($gym) {
            $members = User::role(Roles::MEMBER)->where('gym_id', $gym->id)->where('status', 'active')->pluck('id');

            // Weight lost: first vs latest log per member, counting only decreases.
            $lost = DB::table('weight_logs as w')
                ->whereIn('w.user_id', $members)
                ->select('w.user_id', DB::raw('MAX(w.logged_on) as last_on'), DB::raw('MIN(w.logged_on) as first_on'))
                ->groupBy('w.user_id')->get()
                ->sum(function ($r) {
                    $first = DB::table('weight_logs')->where('user_id', $r->user_id)->where('logged_on', $r->first_on)->value('weight_kg');
                    $last = DB::table('weight_logs')->where('user_id', $r->user_id)->where('logged_on', $r->last_on)->value('weight_kg');

                    return max(0, (float) $first - (float) $last);
                });

            return [
                'members' => $members->count(),
                'trainers' => User::role(Roles::TRAINER)->where('gym_id', $gym->id)->where('status', 'active')->count(),
                'kg_lost' => (int) round($lost),
                'scans' => BodyScan::whereIn('user_id', $members)->where('status', 'completed')->count(),
            ];
        });
    }
}
