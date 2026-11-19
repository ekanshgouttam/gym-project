<?php

namespace Tests\Feature;

use App\Jobs\AnalyzeBodyScan;
use App\Models\BodyScan;
use App\Models\Gym;
use App\Models\Membership;
use App\Models\MembershipPlan;
use App\Models\User;
use App\Services\Ai\BodyScanAnalyzer;
use App\Support\Roles;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\MakesPhotos;
use Tests\TestCase;

class MemberPortalTest extends TestCase
{
    use RefreshDatabase, MakesPhotos;

    /** @var Gym */
    private $gym;
    /** @var User */
    private $member;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        Storage::fake('local');

        $this->gym = Gym::create(['name' => 'Alpha', 'slug' => 'alpha']);
        $this->member = User::factory()->create(['gym_id' => $this->gym->id]);
        $this->member->assignRole(Roles::MEMBER);
        $this->member->memberProfile()->create(['member_code' => 'IF-ALP1-00001', 'height_cm' => 170, 'starting_weight_kg' => 80, 'fitness_goal' => 'fat_loss', 'gender' => 'male', 'date_of_birth' => '1995-01-01']);
    }

    private function activate(): void
    {
        $plan = MembershipPlan::create(['name' => 'Annual', 'slug' => 'annual', 'duration_months' => 12, 'price' => 19999]);
        Membership::create(['user_id' => $this->member->id, 'gym_id' => $this->gym->id, 'membership_plan_id' => $plan->id, 'status' => 'pending', 'amount' => 19999])
            ->load('plan')->activate('admin');
    }

    public function test_every_member_section_renders()
    {
        $this->activate();
        foreach (['dashboard', 'profile', 'membership', 'attendance', 'scanner', 'transformation', 'progress', 'weight', 'measurements', 'workouts', 'diet', 'notifications', 'settings'] as $page) {
            $this->actingAs($this->member)->get(route('member.' . $page))->assertOk();
        }
    }

    public function test_weight_and_measurement_logging()
    {
        $this->actingAs($this->member)->post(route('member.weight.store'), ['logged_on' => today()->toDateString(), 'weight_kg' => 78.2])->assertRedirect();
        $this->actingAs($this->member)->post(route('member.weight.store'), ['logged_on' => today()->toDateString(), 'weight_kg' => 78.0])->assertRedirect();
        $this->assertSame(1, $this->member->weightLogs()->count(), 'same-day entry is replaced');

        $this->actingAs($this->member)->post(route('member.weight.store'), ['logged_on' => today()->addDay()->toDateString(), 'weight_kg' => 70])->assertSessionHasErrors('logged_on');
        $this->actingAs($this->member)->post(route('member.measurements.store'), ['measured_on' => today()->toDateString()])->assertSessionHasErrors('chest_cm');
        $this->actingAs($this->member)->post(route('member.measurements.store'), ['measured_on' => today()->toDateString(), 'waist_cm' => 84])->assertRedirect();
    }

    public function test_members_cannot_touch_other_members_data()
    {
        $other = User::factory()->create(['gym_id' => $this->gym->id]);
        $other->assignRole(Roles::MEMBER);
        $log = $other->weightLogs()->create(['logged_on' => today(), 'weight_kg' => 70]);
        $scan = BodyScan::create(['user_id' => $other->id, 'front_path' => 'x.jpg', 'side_path' => 'y.jpg', 'status' => 'completed']);

        $this->actingAs($this->member)->delete(route('member.weight.destroy', $log))->assertNotFound();
        $this->actingAs($this->member)->get(route('member.scanner.show', $scan))->assertNotFound();
        $this->actingAs($this->member)->get(route('media.scan', [$scan, 'front']))->assertNotFound();
        $this->actingAs($this->member)->get(route('media.avatar', $other))->assertNotFound();
        $this->actingAs($this->member)->get(route('member.transformation', ['from' => $scan->id]))->assertOk(); // foreign id ignored
    }

    private function upload(array $extra = [])
    {
        return $this->actingAs($this->member)->post(route('member.scanner.store'), array_merge($this->scanPhotos(), ['weight_kg' => 79, 'height_cm' => 170, 'consent' => '1'], $extra));
    }

    public function test_first_scan_is_immediate_and_later_scans_wait_30_days()
    {
        config(['services.anthropic.api_key' => 'k']);
        Bus::fake();

        // First scan: allowed immediately (no membership needed for the baseline).
        $this->upload()->assertRedirect();
        $first = BodyScan::firstOrFail();
        $this->assertTrue($first->is_baseline);
        Bus::assertDispatchedAfterResponse(AnalyzeBodyScan::class);

        $this->activate();

        // Second scan right away, and at 29 days 23 h: rejected on the server.
        $this->upload()->assertSessionHasErrors('front');
        $this->travelTo($first->created_at->copy()->addDays(30)->subHour());
        $this->upload()->assertSessionHasErrors('front');
        $this->assertSame(1, BodyScan::count());

        // At 30 days: accepted.
        $this->travelTo($first->created_at->copy()->addDays(30)->addMinute());
        $this->upload()->assertRedirect();
        $this->assertSame(2, BodyScan::count());
        $this->assertFalse(BodyScan::latest('id')->first()->is_baseline);
        $this->travelBack();
    }

    public function test_scanner_page_shows_countdown_and_disabled_upload()
    {
        $this->activate();
        BodyScan::create(['user_id' => $this->member->id, 'is_baseline' => true, 'front_path' => 'a', 'side_path' => 'b', 'back_path' => 'c', 'status' => 'completed', 'physique_score' => 50]);

        $this->actingAs($this->member)->get(route('member.scanner'))
            ->assertOk()
            ->assertSee('data-countdown', false)
            ->assertSee('Upload locked')
            ->assertSee(now()->addDays(30)->format('d M Y'));
    }

    public function test_photo_quality_checks_reject_bad_photos_and_store_nothing()
    {
        config(['services.anthropic.api_key' => 'k']);
        Bus::fake();

        $this->upload(['front' => $this->photo('dark.jpg', 600, 800, 10)])->assertSessionHasErrors('front');
        $this->upload(['side' => $this->photo('tiny.jpg', 200, 300)])->assertSessionHasErrors('side');
        $this->upload(['back' => null])->assertSessionHasErrors('back'); // back photo is required

        $this->assertSame(0, BodyScan::count());
        $this->assertEmpty(Storage::disk('local')->allFiles('scans'));
    }

    public function test_duplicate_photos_are_rejected_and_originals_are_kept_unchanged()
    {
        config(['services.anthropic.api_key' => 'k']);
        Bus::fake();
        $front = $this->photo('front.jpg');
        $frontBytes = file_get_contents($front->getRealPath());

        // Same photo used twice in one scan.
        $this->upload(['front' => $front, 'side' => $front])->assertSessionHasErrors('front');

        $this->upload(['front' => $front])->assertRedirect();
        $scan = BodyScan::firstOrFail();
        $this->assertSame($frontBytes, Storage::disk('local')->get($scan->originals['front']['path']), 'original stored byte-for-byte');
        $this->assertSame(hash('sha256', $frontBytes), $scan->originals['front']['sha256']);
        $this->assertNotSame($scan->originals['front']['path'], $scan->front_path, 'display copy is separate');

        // Re-using a photo from an earlier scan, even after 30 days, is rejected.
        $this->activate();
        $this->travel(31)->days();
        $this->upload(['front' => UploadedFileCopy::of($frontBytes)])->assertSessionHasErrors('front');
        $this->assertSame(1, BodyScan::count());
        $this->travelBack();
    }

    public function test_admin_corrections_are_audited_and_void_reopens_uploads()
    {
        $this->activate();
        $scan = BodyScan::create(['user_id' => $this->member->id, 'is_baseline' => true, 'front_path' => 'a', 'side_path' => 'b', 'back_path' => 'c', 'status' => 'completed', 'weight_kg' => 80]);
        $admin = User::factory()->create(['gym_id' => $this->gym->id]);
        $admin->assignRole(Roles::GYM_ADMIN);
        $outsider = User::factory()->create(['gym_id' => Gym::create(['name' => 'Beta', 'slug' => 'beta'])->id]);
        $outsider->assignRole(Roles::GYM_ADMIN);

        $this->actingAs($outsider)->post(route('manage.scans.void', $scan), ['reason' => 'not my gym'])->assertForbidden();
        $this->actingAs($this->member)->post(route('manage.scans.void', $scan), ['reason' => 'members cannot'])->assertForbidden();
        $this->actingAs($admin)->post(route('manage.scans.void', $scan), [])->assertSessionHasErrors('reason');

        $this->actingAs($admin)->post(route('manage.scans.correct', $scan), ['weight_kg' => 78.5, 'reason' => 'Scale was miscalibrated'])->assertRedirect();
        $this->assertSame(78.5, $scan->fresh()->weight_kg);

        $this->assertFalse(app(\App\Services\BodyScanService::class)->eligibility($this->member)['eligible']);
        $this->actingAs($admin)->post(route('manage.scans.void', $scan), ['reason' => 'Photos were of another person'])->assertRedirect();
        $this->assertNotNull($scan->fresh()->voided_at);
        $this->assertTrue(app(\App\Services\BodyScanService::class)->eligibility($this->member)['eligible']);
        $this->assertSame(1, BodyScan::count(), 'voided scans are kept');

        $this->actingAs($admin)->get(route('manage.scans.index', $this->member))->assertOk()->assertSee('Photos were of another person');

        $logs = \App\Models\ScanAuditLog::where('member_id', $this->member->id)->pluck('action')->all();
        $this->assertSame(['corrected', 'voided'], $logs);
        $entry = \App\Models\ScanAuditLog::where('action', 'corrected')->first();
        $this->assertEquals(['from' => 80, 'to' => 78.5], $entry->changes['weight_kg']);

        $this->expectException(\LogicException::class);
        $entry->update(['reason' => 'tampered']);
    }

    public function test_admin_override_allows_exactly_one_early_upload()
    {
        config(['services.anthropic.api_key' => 'k']);
        Bus::fake();
        $this->activate();
        $this->upload()->assertRedirect();

        $admin = User::factory()->create(['gym_id' => $this->gym->id]);
        $admin->assignRole(Roles::GYM_ADMIN);
        $this->actingAs($admin)->post(route('manage.scans.override', $this->member), ['days' => 7, 'reason' => 'Baseline photos taken in a dark room'])->assertRedirect();

        $this->upload()->assertRedirect();
        $this->assertSame(2, BodyScan::count());
        $this->upload()->assertSessionHasErrors('front');
        $this->assertSame(2, BodyScan::count());
        $this->assertNotNull(\App\Models\BodyScanOverride::first()->used_at);
    }

    public function test_analysis_job_sends_three_photos_and_stores_visual_report()
    {
        config(['services.anthropic.api_key' => 'test-key', 'services.anthropic.model' => 'claude-opus-5-5']);
        foreach (['f', 's', 'b'] as $p) {
            Storage::disk('local')->put("scans/{$p}.jpg", 'jpegbytes');
        }
        $scan = BodyScan::create(['user_id' => $this->member->id, 'front_path' => 'scans/f.jpg', 'side_path' => 'scans/s.jpg', 'back_path' => 'scans/b.jpg', 'weight_kg' => 79, 'status' => 'pending']);

        Http::fake(['api.anthropic.com/*' => Http::response([
            'model' => 'claude-opus-5-5', 'stop_reason' => 'end_turn',
            'content' => [['type' => 'thinking', 'thinking' => ''], ['type' => 'text', 'text' => json_encode($this->visualReport())]],
        ])]);

        (new AnalyzeBodyScan($scan))->handle(app(BodyScanAnalyzer::class));

        $scan->refresh();
        $this->assertSame('completed', $scan->status);
        $this->assertSame(61, $scan->physique_score);
        $this->assertNull($scan->body_fat_estimate);
        $this->assertSame(1, $this->member->notifications()->count());

        Http::assertSent(function ($request) {
            $body = $request->data();
            $schema = json_encode($body['output_config']['format']['schema']);

            return $request->hasHeader('anthropic-version', '2026-10-21')
                && $body['model'] === 'claude-opus-5-5'
                && $body['fallbacks'] === 'default'
                && collect($body['messages'][0]['content'])->where('type', 'image')->count() === 3
                && strpos($body['system'], 'Never state or imply a body-fat percentage') !== false
                && strpos($schema, 'body_fat') === false;
        });
    }

    public function test_failed_analysis_keeps_the_scan_and_can_be_retried()
    {
        config(['services.anthropic.api_key' => 'k']);
        Storage::disk('local')->put('f.jpg', 'x');
        Http::fake(['api.anthropic.com/*' => Http::response(['stop_reason' => 'refusal', 'content' => []])]);
        $scan = BodyScan::create(['user_id' => $this->member->id, 'front_path' => 'f.jpg', 'side_path' => 'f.jpg', 'status' => 'pending']);

        (new AnalyzeBodyScan($scan))->handle(app(BodyScanAnalyzer::class));
        $this->assertSame('failed', $scan->fresh()->status);
        $this->assertTrue($scan->fresh()->canRetryAnalysis());

        Bus::fake();
        $this->actingAs($this->member)->post(route('member.scanner.retry', $scan))->assertRedirect();
        $this->assertSame('pending', $scan->fresh()->status);
        Bus::assertDispatchedAfterResponse(AnalyzeBodyScan::class);
    }

    public function test_compare_any_two_scans_with_ai_summary()
    {
        config(['services.anthropic.api_key' => 'k']);
        $this->activate();
        $mk = function ($daysAgo, $weight) {
            foreach (['f', 's', 'b'] as $p) {
                Storage::disk('local')->put("scans/{$daysAgo}{$p}.jpg", 'jpg');
            }
            $s = BodyScan::create(['user_id' => $this->member->id, 'front_path' => "scans/{$daysAgo}f.jpg", 'side_path' => "scans/{$daysAgo}s.jpg", 'back_path' => "scans/{$daysAgo}b.jpg", 'weight_kg' => $weight, 'status' => 'completed', 'physique_score' => 50]);
            $s->forceFill(['created_at' => now()->subDays($daysAgo)])->save();

            return $s;
        };
        $jan = $mk(150, 82);
        $feb = $mk(120, 80.5);
        $jun = $mk(5, 76);
        $this->member->bodyMeasurements()->create(['measured_on' => now()->subDays(150)->toDateString(), 'waist_cm' => 90]);
        $this->member->bodyMeasurements()->create(['measured_on' => now()->subDays(4)->toDateString(), 'waist_cm' => 84]);

        // Any pair, in either order; page shows range, weight and measurement differences.
        $this->actingAs($this->member)->get(route('member.transformation', ['from' => $jun->id, 'to' => $jan->id]))
            ->assertOk()
            ->assertSee($jan->created_at->format('d M Y') . ' → ' . $jun->created_at->format('d M Y'))
            ->assertSee('-6')       // weight difference
            ->assertSee('90 → 84'); // waist

        $this->actingAs($this->member)->get(route('member.transformation', ['from' => $jan->id, 'to' => $feb->id]))->assertOk()->assertSee('-1.5');

        Http::fake(['api.anthropic.com/*' => Http::response(['stop_reason' => 'end_turn', 'content' => [['type' => 'text', 'text' => json_encode([
            'comparable' => true, 'comparability_notes' => 'Similar lighting.', 'changes' => [['area' => 'Waist', 'observation' => 'Narrower from the front.', 'direction' => 'improved']],
            'posture_change' => 'More upright.', 'overall_direction' => 'noticeable_progress', 'summary' => 'Clear visible progress.', 'recommendations' => ['Keep going'],
        ])]]])]);

        $this->actingAs($this->member)->post(route('member.transformation.compare'), ['from' => $jan->id, 'to' => $jun->id])->assertRedirect();
        $comparison = \App\Models\ScanComparison::firstOrFail();
        (new \App\Jobs\GenerateScanComparison($comparison))->handle(app(BodyScanAnalyzer::class));

        $this->assertSame('completed', $comparison->fresh()->status);
        Http::assertSent(function ($r) {
            return collect($r->data()['messages'][0]['content'])->where('type', 'image')->count() === 6
                && strpos(collect($r->data()['messages'][0]['content'])->where('type', 'text')->last()['text'], 'Waist: 90 cm → 84 cm') !== false;
        });
        $this->actingAs($this->member)->get(route('member.transformation', ['from' => $jan->id, 'to' => $jun->id]))->assertSee('Clear visible progress.');

        // Voided scans and other members' scans can't be compared.
        $feb->forceFill(['voided_at' => now()])->save();
        $this->actingAs($this->member)->post(route('member.transformation.compare'), ['from' => $jan->id, 'to' => $feb->id])->assertNotFound();
    }

    private function visualReport(): array
    {
        return [
            'image_quality_ok' => true, 'image_quality_issue' => '',
            'posture' => ['summary' => 'Slight anterior pelvic tilt.', 'observations' => ['Shoulders level']],
            'proportions' => 'Shoulders wider than hips.', 'body_shape' => 'Athletic frame.', 'visual_leanness' => 'moderate',
            'visible_definition' => ['upper_body' => 'moderate', 'core' => 'low', 'lower_body' => 'moderate'],
            'observations' => ['Visible deltoid definition'], 'strengths' => ['Good symmetry'], 'focus_areas' => ['Core'],
            'recommendations' => ['Add 2 core sessions a week'], 'visual_score' => 61, 'changes_since_last' => '',
            'photo_consistency_tips' => [], 'summary' => 'Solid base to build on.',
        ];
    }
}

/** Wraps raw bytes as an upload, to re-submit an identical photo. */
class UploadedFileCopy
{
    public static function of(string $bytes): \Illuminate\Http\UploadedFile
    {
        return \Illuminate\Http\UploadedFile::fake()->createWithContent('copy.jpg', $bytes);
    }
}
