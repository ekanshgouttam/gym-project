<?php

namespace Tests\Feature;

use App\Models\Gym;
use App\Models\Membership;
use App\Models\MembershipPlan;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\MembershipAwaitingApproval;
use App\Services\Sms\SmsSender;
use App\Support\Roles;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\MakesPhotos;
use Tests\TestCase;

class JoinAndMembershipTest extends TestCase
{
    use RefreshDatabase, MakesPhotos;

    /** @var Gym */
    private $gym;
    /** @var MembershipPlan */
    private $plan;
    /** @var string|null last OTP "sent" */
    private $sentCode;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        Storage::fake('local');

        $this->gym = Gym::create(['name' => 'Alpha Gym', 'slug' => 'alpha-gym']);
        $this->plan = MembershipPlan::create(['name' => 'Quarterly', 'slug' => 'quarterly', 'duration_months' => 3, 'price' => 6499, 'features' => []]);

        $test = $this;
        $this->app->instance(SmsSender::class, new class($test) implements SmsSender {
            private $t;
            public function __construct($t) { $this->t = $t; }
            public function sendOtp(string $phone, string $code): void { $this->t->captureCode($code); }
        });
    }

    public function captureCode(string $code): void
    {
        $this->sentCode = $code;
    }

    private function verifyPhone(string $phone = '9820012345'): void
    {
        $token = $this->gym->join_token;
        $this->postJson(route('join.otp.send', $token), ['phone' => $phone])->assertOk();
        $this->assertNotNull($this->sentCode);
        $this->postJson(route('join.otp.verify', $token), ['phone' => $phone, 'code' => $this->sentCode])->assertOk();
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Asha Patel',
            'email' => 'asha@example.test',
            'date_of_birth' => now()->subYears(25)->toDateString(),
            'height_cm' => 162,
            'weight_kg' => 64.5,
            'fitness_goal' => 'fat_loss',
            'photo' => UploadedFile::fake()->image('me.jpg', 600, 600),
            'front' => $this->photo('front.jpg'),
            'side' => $this->photo('side.jpg'),
            'back' => $this->photo('back.jpg'),
            'scan_consent' => '1',
            'emergency_contact_name' => 'Ravi Patel',
            'emergency_contact_phone' => '9820099999',
            'membership_plan_id' => $this->plan->id,
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'terms' => '1',
        ], $overrides);
    }

    public function test_qr_scans_are_counted_once_and_credited_on_registration()
    {
        $token = $this->gym->join_token;
        $this->get(route('join.show', $token) . '?src=qr')->assertOk();
        $this->get(route('join.show', $token) . '?src=qr')->assertOk();
        $this->get(route('join.show', $token))->assertOk();
        $this->assertSame(1, \App\Models\JoinVisit::where('gym_id', $this->gym->id)->where('source', 'qr')->count());
        $this->assertSame(0, \App\Models\JoinVisit::where('source', 'link')->count());

        $this->verifyPhone();
        $this->post(route('join.store', $token), $this->payload())->assertRedirect(route('member.membership'));
        $this->assertSame('qr', User::where('email', 'asha@example.test')->first()->memberProfile->source);
        $this->assertStringEndsWith('?src=qr', $this->gym->qrUrl());
    }

    public function test_each_gym_gets_a_unique_join_token_and_qr_page()
    {
        $other = Gym::create(['name' => 'Beta', 'slug' => 'beta']);
        $this->assertNotSame($this->gym->join_token, $other->join_token);

        $this->get(route('join.show', $this->gym->join_token))->assertOk()->assertSee('Join Alpha Gym');
        $this->get('/join/not-a-real-token')->assertNotFound();

        $admin = User::factory()->create(['gym_id' => $this->gym->id]);
        $admin->assignRole(Roles::GYM_ADMIN);
        $this->actingAs($admin)->get(route('manage.gyms.qr', $this->gym))->assertOk()->assertSee('<svg', false);
        $this->actingAs($admin)->get(route('manage.gyms.qr', $other))->assertForbidden();
    }

    public function test_registration_requires_otp_verification()
    {
        $this->post(route('join.store', $this->gym->join_token), $this->payload())->assertSessionHasErrors('phone');
        $this->assertDatabaseMissing('users', ['email' => 'asha@example.test']);
    }

    public function test_wrong_otp_is_rejected_and_attempts_are_limited()
    {
        $token = $this->gym->join_token;
        $this->postJson(route('join.otp.send', $token), ['phone' => '9820012345'])->assertOk();

        for ($i = 0; $i < 5; $i++) {
            $this->postJson(route('join.otp.verify', $token), ['phone' => '9820012345', 'code' => '000000'])->assertStatus(422);
        }
        // Even the right code fails once attempts are exhausted.
        $this->postJson(route('join.otp.verify', $token), ['phone' => '9820012345', 'code' => $this->sentCode])->assertStatus(422);
    }

    public function test_full_qr_registration_creates_member_id_and_pending_membership()
    {
        Notification::fake();
        $admin = User::factory()->create(['gym_id' => $this->gym->id]);
        $admin->assignRole(Roles::GYM_ADMIN);

        $this->verifyPhone();
        $this->post(route('join.store', $this->gym->join_token), $this->payload())
            ->assertRedirect(route('member.membership'));

        $user = User::where('email', 'asha@example.test')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertTrue($user->hasRole(Roles::MEMBER));
        $this->assertSame('+919820012345', $user->phone);
        $this->assertSame($this->gym->id, $user->gym_id);
        $this->assertMatchesRegularExpression('/^IF-ALP\d+-00001$/', $user->memberProfile->member_code);
        $this->assertNotNull($user->memberProfile->phone_verified_at);
        $this->assertSame('+919820099999', $user->memberProfile->emergency_contact_phone);
        Storage::disk('local')->assertExists($user->avatar_path);

        // Initial Baseline Scan with all three photos, weight and height.
        $baseline = $user->bodyScans()->firstOrFail();
        $this->assertTrue($baseline->is_baseline);
        $this->assertSame(64.5, $baseline->weight_kg);
        $this->assertSame(162.0, $baseline->height_cm);
        $this->assertCount(3, $baseline->originals);
        Storage::disk('local')->assertExists($baseline->back_path);
        $this->assertSame('baseline_created', \App\Models\ScanAuditLog::first()->action);

        $membership = $user->memberships()->first();
        $this->assertSame('pending', $membership->status);
        $this->assertFalse($user->hasActiveMembership());
        Notification::assertSentTo($admin, MembershipAwaitingApproval::class);

        // Member IDs are sequential per branch.
        auth()->logout();
        $this->sentCode = null;
        $this->verifyPhone('9820012346');
        $this->post(route('join.store', $this->gym->join_token), $this->payload(['email' => 'b@example.test', 'emergency_contact_phone' => '9820088888']));
        $this->assertStringEndsWith('-00002', User::where('email', 'b@example.test')->first()->memberProfile->member_code);
    }

    public function test_admin_approval_activates_membership_for_plan_duration()
    {
        [$member, $membership] = $this->pendingMember();
        $admin = User::factory()->create(['gym_id' => $this->gym->id]);
        $admin->assignRole(Roles::GYM_ADMIN);

        $this->actingAs($admin)->post(route('manage.memberships.approve', $membership))->assertRedirect();

        $membership->refresh();
        $this->assertSame('active', $membership->status);
        $this->assertSame('admin', $membership->activated_via);
        $this->assertSame(today()->toDateString(), $membership->starts_on->toDateString());
        $this->assertSame(today()->addMonthsNoOverflow(3)->subDay()->toDateString(), $membership->ends_on->toDateString());
        $this->assertTrue($member->fresh()->hasActiveMembership());
        $this->assertSame(1, $member->notifications()->count());
    }

    public function test_admin_of_another_gym_cannot_approve()
    {
        [, $membership] = $this->pendingMember();
        $other = Gym::create(['name' => 'Beta', 'slug' => 'beta']);
        $admin = User::factory()->create(['gym_id' => $other->id]);
        $admin->assignRole(Roles::GYM_ADMIN);

        $this->actingAs($admin)->post(route('manage.memberships.approve', $membership))->assertForbidden();
        $this->assertSame('pending', $membership->fresh()->status);
    }

    public function test_razorpay_payment_with_valid_signature_activates_membership()
    {
        config(['services.razorpay.key_id' => 'rzp_test_x', 'services.razorpay.key_secret' => 'secret_x']);
        Http::fake(['api.razorpay.com/*' => Http::response(['id' => 'order_ABC', 'amount' => 649900, 'currency' => 'INR'])]);
        [$member, $membership] = $this->pendingMember();

        $this->actingAs($member)->postJson(route('member.membership.checkout', $membership))
            ->assertOk()->assertJson(['order_id' => 'order_ABC', 'amount' => 649900]);

        // Tampered signature is rejected.
        $this->actingAs($member)->postJson(route('member.payments.verify'), [
            'razorpay_order_id' => 'order_ABC', 'razorpay_payment_id' => 'pay_1', 'razorpay_signature' => 'forged',
        ])->assertStatus(422);
        $this->assertSame('pending', $membership->fresh()->status);

        $signature = hash_hmac('sha256', 'order_ABC|pay_1', 'secret_x');
        $this->actingAs($member)->postJson(route('member.payments.verify'), [
            'razorpay_order_id' => 'order_ABC', 'razorpay_payment_id' => 'pay_1', 'razorpay_signature' => $signature,
        ])->assertOk();

        $this->assertSame('active', $membership->fresh()->status);
        $this->assertSame('payment', $membership->fresh()->activated_via);
        $this->assertSame('paid', Payment::first()->status);
    }

    public function test_razorpay_webhook_requires_valid_signature_and_is_idempotent()
    {
        config(['services.razorpay.webhook_secret' => 'whsec']);
        [, $membership] = $this->pendingMember();
        Payment::create(['user_id' => $membership->user_id, 'membership_id' => $membership->id, 'gateway_order_id' => 'order_W', 'amount_paise' => 649900]);

        $body = json_encode(['event' => 'payment.captured', 'payload' => ['payment' => ['entity' => ['id' => 'pay_W', 'order_id' => 'order_W', 'amount' => 649900]]]]);

        $this->call('POST', route('webhooks.razorpay'), [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_X_RAZORPAY_SIGNATURE' => 'bad'], $body)->assertStatus(400);
        $this->assertSame('pending', $membership->fresh()->status);

        $sig = hash_hmac('sha256', $body, 'whsec');
        foreach ([1, 2] as $_) {
            $this->call('POST', route('webhooks.razorpay'), [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_X_RAZORPAY_SIGNATURE' => $sig], $body)->assertOk();
        }
        $this->assertSame('active', $membership->fresh()->status);
        $this->assertSame(1, $membership->user->notifications()->count());
    }

    public function test_renewal_starts_after_current_membership_ends()
    {
        [$member, $first] = $this->pendingMember();
        $first->load('plan')->activate('admin');

        $renewal = Membership::create(['user_id' => $member->id, 'gym_id' => $this->gym->id, 'membership_plan_id' => $this->plan->id, 'status' => 'pending', 'amount' => 6499]);
        $renewal->load('plan')->activate('payment');

        $this->assertSame($first->fresh()->ends_on->addDay()->toDateString(), $renewal->fresh()->starts_on->toDateString());
    }

    private function pendingMember(): array
    {
        $member = User::factory()->create(['gym_id' => $this->gym->id]);
        $member->assignRole(Roles::MEMBER);
        $membership = Membership::create(['user_id' => $member->id, 'gym_id' => $this->gym->id, 'membership_plan_id' => $this->plan->id, 'status' => 'pending', 'amount' => 6499]);

        return [$member, $membership];
    }
}
