<?php

namespace Tests\Feature;

use App\Models\Gym;
use App\Models\MembershipPlan;
use App\Models\User;
use App\Services\Sms\SmsSender;
use App\Support\Roles;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\MakesPhotos;
use Tests\TestCase;

class MobileApiTest extends TestCase
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

    public function test_app_version_reports_whether_an_update_is_needed()
    {
        config(['mobile.android' => ['latest' => '1.4.0', 'minimum' => '1.2.0', 'store_url' => 'https://play.example/app']]);

        $this->getJson('/api/v1/app-version?platform=android')->assertOk()
            ->assertExactJson(['platform' => 'android', 'latest_version' => '1.4.0', 'minimum_version' => '1.2.0', 'store_url' => 'https://play.example/app']);
        $this->getJson('/api/v1/app-version?platform=android&version=1.1.9')->assertJson(['update' => 'required']);
        $this->getJson('/api/v1/app-version?platform=android&version=1.2.0')->assertJson(['update' => 'optional']);
        $this->getJson('/api/v1/app-version?platform=android&version=1.4.0')->assertJson(['update' => 'none']);
        $this->getJson('/api/v1/app-version?platform=windows')->assertStatus(422);
    }

    public function test_any_role_can_log_in_use_the_token_and_log_out()
    {
        $trainer = User::factory()->create(['gym_id' => $this->gym->id, 'email' => 'coach@example.test', 'password' => bcrypt('secret123')]);
        $trainer->assignRole(Roles::TRAINER);

        $this->postJson('/api/v1/auth/login', ['email' => 'coach@example.test', 'password' => 'wrong', 'device_name' => 'test'])
            ->assertStatus(422)->assertJsonValidationErrors('email');

        $token = $this->postJson('/api/v1/auth/login', ['email' => 'coach@example.test', 'password' => 'secret123', 'device_name' => 'Pixel 8'])
            ->assertOk()
            ->assertJsonPath('user.role', Roles::TRAINER)
            ->assertJsonPath('user.gym.name', 'Alpha Gym')
            ->json('token');

        $this->withToken($token)->getJson('/api/v1/auth/me')->assertOk()->assertJsonPath('user.email', 'coach@example.test');
        $this->withToken($token)->postJson('/api/v1/auth/logout')->assertOk();
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_suspended_users_cannot_log_in()
    {
        $user = User::factory()->create(['email' => 'gone@example.test', 'password' => bcrypt('secret123'), 'status' => 'suspended']);
        $user->assignRole(Roles::MEMBER);

        $this->postJson('/api/v1/auth/login', ['email' => 'gone@example.test', 'password' => 'secret123', 'device_name' => 'test'])
            ->assertStatus(422);
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_member_logs_in_with_mobile_number_and_otp()
    {
        $member = User::factory()->create(['gym_id' => $this->gym->id, 'phone' => '+919820012345']);
        $member->assignRole(Roles::MEMBER);

        // Any common format of the number works.
        $this->postJson('/api/v1/auth/otp', ['phone' => '098200 12345'])->assertOk();
        $this->assertNotNull($this->sentCode);

        $this->postJson('/api/v1/auth/otp/verify', ['phone' => '9820012345', 'code' => '000000', 'device_name' => 'Pixel 8'])
            ->assertStatus(422)->assertJsonValidationErrors('code');

        $token = $this->postJson('/api/v1/auth/otp/verify', ['phone' => '9820012345', 'code' => $this->sentCode, 'device_name' => 'Pixel 8'])
            ->assertOk()
            ->assertJsonPath('user.id', $member->id)
            ->assertJsonPath('user.role', Roles::MEMBER)
            ->json('token');
        $this->withToken($token)->getJson('/api/v1/auth/me')->assertOk();

        // A used code cannot log in again.
        $this->postJson('/api/v1/auth/otp/verify', ['phone' => '9820012345', 'code' => $this->sentCode, 'device_name' => 'x'])->assertStatus(422);
    }

    public function test_mobile_otp_login_is_only_for_active_members()
    {
        $this->postJson('/api/v1/auth/otp', ['phone' => '9820012345'])->assertStatus(422)->assertJsonValidationErrors('phone');

        $trainer = User::factory()->create(['gym_id' => $this->gym->id, 'phone' => '+919820011111']);
        $trainer->assignRole(Roles::TRAINER);
        $this->postJson('/api/v1/auth/otp', ['phone' => '9820011111'])->assertStatus(422);

        $suspended = User::factory()->create(['gym_id' => $this->gym->id, 'phone' => '+919820022222', 'status' => 'suspended']);
        $suspended->assignRole(Roles::MEMBER);
        $this->postJson('/api/v1/auth/otp', ['phone' => '9820022222'])->assertStatus(422);

        $this->assertNull($this->sentCode);
    }

    public function test_member_registers_from_the_app_after_otp()
    {
        $token = $this->gym->join_token;

        $this->getJson("/api/v1/join/{$token}")->assertOk()
            ->assertJsonPath('gym.name', 'Alpha Gym')
            ->assertJsonPath('plans.0.id', $this->plan->id);

        // Registering before the OTP is verified is refused.
        $this->postJson("/api/v1/join/{$token}", $this->payload())->assertStatus(422)->assertJsonValidationErrors('phone');

        $this->postJson("/api/v1/join/{$token}/otp", ['phone' => '9820012345'])->assertOk();
        $this->postJson("/api/v1/join/{$token}/otp/verify", ['phone' => '9820012345', 'code' => $this->sentCode])->assertOk();

        $apiToken = $this->postJson("/api/v1/join/{$token}", $this->payload())
            ->assertCreated()
            ->assertJsonPath('user.role', Roles::MEMBER)
            ->json('token');

        $member = User::where('email', 'asha@example.test')->first();
        $this->assertSame('+919820012345', $member->phone);
        $this->assertSame('app', $member->memberProfile->source);
        $this->assertSame('pending', $member->memberships()->first()->status);
        $this->withToken($apiToken)->getJson('/api/v1/auth/me')->assertJsonPath('user.member_code', $member->memberProfile->member_code);

        // The verified OTP is used up: it cannot register a second account.
        $this->postJson("/api/v1/join/{$token}", $this->payload(['email' => 'other@example.test']))->assertStatus(422);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'phone' => '9820012345',
            'device_name' => 'Pixel 8',
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
}
