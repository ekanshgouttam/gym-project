<?php

namespace Tests\Feature;

use App\Models\Gym;
use App\Models\RegistrationRequest;
use App\Models\User;
use App\Services\Sms\SmsSender;
use App\Support\Roles;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationRequestTest extends TestCase
{
    use RefreshDatabase;

    /** @var Gym */
    private $gym;
    /** @var User */
    private $owner;
    /** @var string|null */
    private $sentCode;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->gym = Gym::create(['name' => 'Alpha Gym', 'slug' => 'alpha-gym', 'city' => 'Pune', 'is_active' => true]);
        $this->owner = User::factory()->create(['gym_id' => $this->gym->id]);
        $this->owner->assignRole(Roles::GYM_ADMIN);

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

    /** Sign up from the app and return the API token. */
    private function register(array $overrides = []): string
    {
        $phone = $overrides['phone'] ?? '9820012345';
        $this->postJson('/api/v1/register/otp', ['phone' => $phone])->assertOk();

        return $this->postJson('/api/v1/register', array_merge([
            'phone' => $phone, 'code' => $this->sentCode, 'name' => 'Asha Patel', 'email' => 'asha@example.test',
            'gym_id' => $this->gym->id, 'device_name' => 'Pixel 8',
        ], $overrides))->assertCreated()->json('token');
    }

    public function test_gym_list_can_be_searched()
    {
        Gym::create(['name' => 'Beta Fitness', 'slug' => 'beta', 'city' => 'Mumbai', 'is_active' => true]);
        Gym::create(['name' => 'Closed Gym', 'slug' => 'closed', 'is_active' => false]);

        $this->getJson('/api/v1/gyms')->assertOk()->assertJsonCount(2, 'gyms');
        $this->getJson('/api/v1/gyms?q=mumbai')->assertOk()->assertJsonCount(1, 'gyms')->assertJsonPath('gyms.0.name', 'Beta Fitness');
    }

    public function test_applicant_registers_and_waits_for_the_gym()
    {
        $token = $this->register();

        $user = User::where('phone', '+919820012345')->firstOrFail();
        $this->assertSame('applicant', $user->status);
        $this->assertNull($user->gym_id);
        $this->assertSame(1, $this->owner->notifications()->count());

        $this->withToken($token)->getJson('/api/v1/auth/me')->assertOk()
            ->assertJsonPath('user.status', 'applicant')
            ->assertJsonPath('user.registration.status', 'pending')
            ->assertJsonPath('user.registration.gym.name', 'Alpha Gym');

        // Mobile + OTP login works while waiting; the website explains why it can't be used yet.
        $this->postJson('/api/v1/auth/otp', ['phone' => '9820012345'])->assertOk();
        $this->postJson('/api/v1/auth/otp/verify', ['phone' => '9820012345', 'code' => $this->sentCode, 'device_name' => 'x'])
            ->assertOk()->assertJsonPath('user.registration.status', 'pending');
    }

    public function test_email_login_works_when_a_password_was_chosen_but_not_on_the_website()
    {
        $this->register(['password' => 'secret123', 'password_confirmation' => 'secret123']);

        $this->postJson('/api/v1/auth/login', ['email' => 'asha@example.test', 'password' => 'secret123', 'device_name' => 'x'])
            ->assertOk()->assertJsonPath('user.status', 'applicant');
        $this->post(route('login'), ['email' => 'asha@example.test', 'password' => 'secret123'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_owner_approves_and_the_applicant_becomes_a_member()
    {
        $this->register(['join_token' => $this->gym->join_token, 'gym_id' => null]);
        $request = RegistrationRequest::firstOrFail();
        $this->assertSame('qr', $request->source);

        $this->actingAs($this->owner)->get(route('gym-admin.registrations.index'))->assertOk()->assertSee('Asha Patel');
        $this->actingAs($this->owner)->post(route('gym-admin.registrations.approve', $request))->assertRedirect();

        $user = $request->user->fresh();
        $this->assertSame('active', $user->status);
        $this->assertSame($this->gym->id, (int) $user->gym_id);
        $this->assertNotNull($user->memberProfile->member_code);
        $this->assertSame('approved', $request->fresh()->status);
        $this->assertSame($this->owner->id, (int) $request->fresh()->decided_by);

        // A second answer is refused.
        $this->actingAs($this->owner)->post(route('gym-admin.registrations.reject', $request))->assertSessionHasErrors('request');
    }

    public function test_rejected_applicant_is_kept_and_can_apply_to_another_gym()
    {
        $token = $this->register();
        $request = RegistrationRequest::firstOrFail();

        $this->actingAs($this->owner)->post(route('gym-admin.registrations.reject', $request), ['reason' => 'Batch is full'])->assertRedirect();
        $this->actingAs($this->owner)->get(route('gym-admin.registrations.index', ['status' => 'rejected']))->assertOk()->assertSee('Batch is full');
        $this->app['auth']->forgetGuards(); // the API calls below are the applicant's, not the owner's web session

        $this->withToken($token)->getJson('/api/v1/registration')->assertOk()
            ->assertJsonPath('registration.status', 'rejected')
            ->assertJsonPath('registration.rejection_reason', 'Batch is full');

        $beta = Gym::create(['name' => 'Beta Fitness', 'slug' => 'beta', 'is_active' => true]);
        $this->withToken($token)->postJson('/api/v1/registration', ['gym_id' => $beta->id])->assertCreated()
            ->assertJsonPath('registration.status', 'pending')
            ->assertJsonPath('registration.gym.name', 'Beta Fitness');
        // Only one open request at a time.
        $this->withToken($token)->postJson('/api/v1/registration', ['gym_id' => $this->gym->id])->assertStatus(422);

        $this->assertSame(2, RegistrationRequest::count());
        $this->assertSame('applicant', User::where('email', 'asha@example.test')->value('status'));
    }

    public function test_owners_only_see_and_answer_their_own_gyms_requests()
    {
        $this->register();
        $request = RegistrationRequest::firstOrFail();

        $other = Gym::create(['name' => 'Beta Fitness', 'slug' => 'beta', 'is_active' => true]);
        $otherOwner = User::factory()->create(['gym_id' => $other->id]);
        $otherOwner->assignRole(Roles::GYM_ADMIN);

        $this->actingAs($otherOwner)->get(route('gym-admin.registrations.index'))->assertOk()->assertDontSee('Asha Patel');
        $this->actingAs($otherOwner)->post(route('gym-admin.registrations.approve', $request))->assertForbidden();
        $this->assertSame('pending', $request->fresh()->status);
    }

    public function test_registration_needs_a_valid_otp_and_unused_phone_and_email()
    {
        $this->postJson('/api/v1/register/otp', ['phone' => '9820012345'])->assertOk();
        $this->postJson('/api/v1/register', [
            'phone' => '9820012345', 'code' => '000000', 'name' => 'A', 'email' => 'a@example.test', 'gym_id' => $this->gym->id, 'device_name' => 'x',
        ])->assertStatus(422)->assertJsonValidationErrors('code');
        $this->assertSame(0, User::where('email', 'a@example.test')->count());

        $member = User::factory()->create(['phone' => '+919820099999', 'email' => 'taken@example.test']);
        $this->postJson('/api/v1/register/otp', ['phone' => '9820099999'])->assertStatus(422)->assertJsonValidationErrors('phone');
        $this->postJson('/api/v1/register', [
            'phone' => '9820012345', 'code' => $this->sentCode, 'name' => 'A', 'email' => 'taken@example.test', 'gym_id' => $this->gym->id, 'device_name' => 'x',
        ])->assertStatus(422)->assertJsonValidationErrors('email');
    }
}
