<?php

namespace Tests\Feature;

use App\Models\AiUsageLog;
use App\Models\BodyScan;
use App\Models\Gym;
use App\Models\GymSubscription;
use App\Models\PlatformSetting;
use App\Models\SubscriptionPayment;
use App\Models\SubscriptionPlan;
use App\Models\SupportRequest;
use App\Models\User;
use App\Services\Ai\BodyScanAnalyzer;
use App\Support\Roles;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class SuperAdminPlatformTest extends TestCase
{
    use RefreshDatabase;

    private Gym $gym;
    private User $super;
    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->gym = Gym::create(['name' => 'Alpha', 'slug' => 'alpha']);
        $this->super = User::factory()->create(['gym_id' => null]);
        $this->super->assignRole(Roles::SUPER_ADMIN);
        $this->owner = User::factory()->create(['gym_id' => $this->gym->id]);
        $this->owner->assignRole(Roles::GYM_ADMIN);
    }

    public function test_every_super_admin_page_loads_and_is_closed_to_gym_owners()
    {
        GymSubscription::startTrial($this->gym);
        $ticket = SupportRequest::create(['gym_id' => $this->gym->id, 'user_id' => $this->owner->id, 'subject' => 'Help', 'message' => 'Hi']);

        $pages = [
            route('super-admin.dashboard'), route('manage.gyms.index'), route('manage.gyms.index', ['filter' => 'active']),
            route('super-admin.subscriptions.index'), route('super-admin.subscriptions.edit', $this->gym->subscription),
            route('super-admin.plans.edit', SubscriptionPlan::first()), route('super-admin.plans.create'),
            route('super-admin.payments.index'), route('super-admin.payments.create'),
            route('super-admin.members'), route('super-admin.revenue'), route('super-admin.ai'),
            route('super-admin.support.index'), route('super-admin.support.show', $ticket), route('super-admin.settings'),
        ];

        foreach ($pages as $url) {
            $this->actingAs($this->super)->get($url)->assertOk();
            $this->actingAs($this->owner)->get($url)->assertForbidden();
        }
    }

    public function test_new_gyms_start_on_a_trial_and_registration_can_be_paused()
    {
        PlatformSetting::put(['trial_days' => 7, 'default_plan' => 'growth']);
        $payload = [
            'gym_name' => 'Iron Den', 'name' => 'Owner', 'email' => 'den@example.test', 'phone' => '9876500000',
            'address' => 'x', 'city' => 'Pune', 'password' => 'secret123', 'password_confirmation' => 'secret123', 'terms' => '1',
        ];

        $this->post(route('register'), $payload)->assertRedirect(route('gym-admin.dashboard'));
        $sub = Gym::where('name', 'Iron Den')->firstOrFail()->subscription;
        $this->assertSame('trial', $sub->status);
        $this->assertSame('growth', $sub->plan->slug);
        $this->assertSame(today()->addDays(7)->toDateString(), $sub->ends_on->toDateString());

        $this->post(route('logout'));
        PlatformSetting::put(['allow_gym_registration' => false]);
        $this->get(route('register'))->assertOk()->assertSee('registrations are paused');
        $this->post(route('register'), ['email' => 'x@example.test'] + $payload)->assertForbidden();
    }

    public function test_recording_a_paid_payment_activates_and_extends_the_subscription()
    {
        $sub = GymSubscription::startTrial($this->gym);

        $this->actingAs($this->super)->post(route('super-admin.payments.store'), [
            'gym_id' => $this->gym->id, 'amount' => 999, 'method' => 'upi', 'reference' => 'UTR1',
            'status' => 'paid', 'paid_at' => today()->toDateString(),
        ])->assertRedirect(route('super-admin.payments.index'));

        $sub->refresh();
        $this->assertSame('active', $sub->status);
        $this->assertSame(today()->addMonth()->toDateString(), $sub->ends_on->toDateString());
        $this->assertSame(999.0, $sub->mrr());

        // A second payment extends from the current end date, not from today.
        $this->post(route('super-admin.payments.store'), [
            'gym_id' => $this->gym->id, 'amount' => 999, 'method' => 'cash', 'status' => 'paid', 'paid_at' => today()->toDateString(),
        ]);
        $this->assertSame(today()->addMonth()->addMonth()->toDateString(), $sub->fresh()->ends_on->toDateString());
        $this->assertSame(1998, (int) SubscriptionPayment::sum('amount'));

        $this->get(route('super-admin.dashboard'))->assertOk()->assertSee('1,998');
    }

    public function test_support_requests_flow_between_gym_owner_and_super_admin()
    {
        $other = Gym::create(['name' => 'Beta', 'slug' => 'beta']);
        $otherTicket = SupportRequest::create(['gym_id' => $other->id, 'subject' => 'Theirs', 'message' => 'x']);

        $this->actingAs($this->owner)->post(route('manage.support.store'), [
            'subject' => 'QR not printing', 'category' => 'technical', 'priority' => 'high', 'message' => 'The poster is blank.',
        ])->assertRedirect();
        $ticket = SupportRequest::where('subject', 'QR not printing')->firstOrFail();
        $this->assertSame($this->gym->id, $ticket->gym_id);

        $this->get(route('manage.support.show', $otherTicket))->assertNotFound();
        $this->post(route('manage.support.store'), ['subject' => 'x', 'category' => 'general', 'priority' => 'urgent', 'message' => 'x'])
            ->assertSessionHasErrors('priority');

        $this->actingAs($this->super)->put(route('super-admin.support.update', $ticket), [
            'status' => 'resolved', 'priority' => 'high', 'admin_reply' => 'Use Chrome to print.',
        ])->assertRedirect();
        $ticket->refresh();
        $this->assertNotNull($ticket->replied_at);
        $this->assertNotNull($ticket->resolved_at);

        $this->actingAs($this->owner)->get(route('manage.support.show', $ticket))->assertOk()->assertSee('Use Chrome to print.');
    }

    public function test_ai_calls_are_logged_with_estimated_cost_and_can_be_switched_off()
    {
        Storage::fake('local');
        config(['services.anthropic.api_key' => 'test-key', 'services.anthropic.model' => 'claude-opus-5-5']);
        $member = User::factory()->create(['gym_id' => $this->gym->id]);
        $member->assignRole(Roles::MEMBER);
        Storage::disk('local')->put('scans/f.jpg', 'x');
        Storage::disk('local')->put('scans/s.jpg', 'x');
        $scan = BodyScan::create(['user_id' => $member->id, 'front_path' => 'scans/f.jpg', 'side_path' => 'scans/s.jpg', 'weight_kg' => 80, 'status' => 'pending']);

        Http::fake(['api.anthropic.com/*' => Http::response([
            'model' => 'claude-opus-5-5', 'stop_reason' => 'refusal', 'content' => [],
            'usage' => ['input_tokens' => 10000, 'output_tokens' => 2000, 'cache_creation_input_tokens' => 0, 'cache_read_input_tokens' => 0],
        ])]);

        try {
            app(BodyScanAnalyzer::class)->analyze($scan);
            $this->fail('A refusal should throw.');
        } catch (RuntimeException $e) {
        }

        $log = AiUsageLog::firstOrFail();
        $this->assertFalse($log->success);
        $this->assertSame($this->gym->id, $log->gym_id);
        $this->assertSame('scan_analysis', $log->kind);
        // 10k input at $4/M + 2k output at $20/M = $0.04 + $0.04
        $this->assertEqualsWithDelta(0.08, $log->cost_usd, 0.000001);

        $this->actingAs($this->super)->get(route('super-admin.ai'))->assertOk()->assertSee('Alpha');

        PlatformSetting::put(['ai_scans_enabled' => false]);
        $this->assertFalse(app(BodyScanAnalyzer::class)->enabled());
    }

    public function test_settings_save_and_validate()
    {
        $this->actingAs($this->super)->put(route('super-admin.settings.update'), [
            'trial_days' => 30, 'default_plan' => 'pro', 'support_email' => 'help@example.test',
            'ai_monthly_budget_usd' => 50, 'usd_to_inr' => 85, 'ai_scans_enabled' => '1',
        ])->assertRedirect();

        $this->assertSame(30, PlatformSetting::get('trial_days'));
        $this->assertSame('pro', PlatformSetting::get('default_plan'));
        $this->assertFalse(PlatformSetting::get('allow_gym_registration'));
        $this->assertTrue(PlatformSetting::get('ai_scans_enabled'));

        $this->put(route('super-admin.settings.update'), ['trial_days' => -1, 'default_plan' => 'nope', 'ai_monthly_budget_usd' => 1, 'usd_to_inr' => 85])
            ->assertSessionHasErrors(['trial_days', 'default_plan']);
    }
}
