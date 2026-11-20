<?php

namespace Tests\Feature;

use App\Models\Gym;
use App\Models\User;
use App\Support\Branding;
use App\Support\Roles;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BrandingTest extends TestCase
{
    use RefreshDatabase;

    /** @var Gym */
    private $gym;
    /** @var User */
    private $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        Storage::fake('local');

        $this->gym = Gym::create(['name' => 'Alpha Gym', 'slug' => 'alpha-gym']);
        $this->owner = User::factory()->create(['gym_id' => $this->gym->id]);
        $this->owner->assignRole(Roles::GYM_ADMIN);
    }

    private function user(string $role, ?int $gymId): User
    {
        $user = User::factory()->create(['gym_id' => $gymId]);
        $user->assignRole($role);

        return $user;
    }

    public function test_owner_saves_branding_and_everyone_in_the_gym_sees_it()
    {
        $this->actingAs($this->owner)->get(route('gym-admin.branding'))->assertOk();

        $this->actingAs($this->owner)->put(route('gym-admin.branding.update'), [
            'primary' => '#1d4ed8', 'accent' => '#f97316', 'theme' => 'light',
            'logo' => UploadedFile::fake()->image('logo.png', 300, 300),
        ])->assertRedirect()->assertSessionHasNoErrors();

        $gym = $this->gym->fresh();
        $this->assertEquals(['primary' => '#1D4ED8', 'accent' => '#F97316', 'theme' => 'light'], $gym->setting('branding'));
        Storage::disk('local')->assertExists($gym->logo_path);

        foreach ([$this->owner, $this->user(Roles::TRAINER, $gym->id), $this->user(Roles::MEMBER, $gym->id)] as $user) {
            $this->actingAs($user)->get(route('dashboard'))->assertRedirect();
            $this->actingAs($user)->get($this->followDashboard($user))
                ->assertOk()
                ->assertSee('theme-light', false)
                ->assertSee('--c-volt-400: 29 78 216', false)
                ->assertSee($gym->logoUrl(), false);
        }
    }

    public function test_other_gyms_and_super_admins_keep_their_own_look()
    {
        $this->gym->update(['settings' => ['branding' => ['primary' => '#1D4ED8', 'accent' => '#F97316', 'theme' => 'light']]]);

        $other = Gym::create(['name' => 'Beta Gym', 'slug' => 'beta-gym']);
        $otherMember = $this->user(Roles::MEMBER, $other->id);
        $this->actingAs($otherMember)->get($this->followDashboard($otherMember))
            ->assertOk()->assertDontSee('theme-light', false)->assertDontSee('29 78 216', false);

        $super = $this->user(Roles::SUPER_ADMIN, null);
        $this->actingAs($super)->get($this->followDashboard($super))
            ->assertOk()->assertDontSee('theme-light', false)->assertDontSee('--c-volt-400:', false);
    }

    public function test_invalid_values_are_rejected_and_reset_restores_defaults()
    {
        $this->actingAs($this->owner)->put(route('gym-admin.branding.update'), ['primary' => 'red', 'accent' => '#F97316', 'theme' => 'neon'])
            ->assertSessionHasErrors(['primary', 'theme']);

        $this->gym->update(['settings' => ['branding' => ['primary' => '#1D4ED8', 'accent' => '#F97316', 'theme' => 'light']]]);
        $this->actingAs($this->owner)->put(route('gym-admin.branding.update'), ['reset' => '1'])->assertRedirect();
        $this->assertEquals(Branding::DEFAULTS, $this->gym->fresh()->setting('branding'));
    }

    public function test_only_the_gym_owner_can_change_branding()
    {
        $trainer = $this->user(Roles::TRAINER, $this->gym->id);
        $this->actingAs($trainer)->get(route('gym-admin.branding'))->assertForbidden();
        $this->actingAs($trainer)->put(route('gym-admin.branding.update'), ['primary' => '#000000', 'accent' => '#000000', 'theme' => 'dark'])->assertForbidden();
    }

    public function test_colours_are_adjusted_to_stay_readable()
    {
        // Yellow on a light theme is unreadable, so it is darkened; navy on dark is lightened.
        $this->assertGreaterThanOrEqual(3.0, Branding::contrast(Branding::readable('#FFD400', '#FFFFFF'), '#FFFFFF'));
        $this->assertGreaterThanOrEqual(3.0, Branding::contrast(Branding::readable('#0A1A40', '#080B11'), '#080B11'));
        // Colours that are already readable are left alone.
        $this->assertSame('#FFD400', Branding::readable('#FFD400', '#080B11'));
        $this->assertSame('#080B11', Branding::textOn('#FFD400'));
        $this->assertSame('#FFFFFF', Branding::textOn('#1D4ED8'));
    }

    public function test_mobile_api_returns_the_gym_branding()
    {
        $this->gym->update(['settings' => ['branding' => ['primary' => '#1D4ED8', 'accent' => '#F97316', 'theme' => 'dark']]]);
        $member = User::factory()->create(['gym_id' => $this->gym->id, 'email' => 'm@example.test', 'password' => bcrypt('secret123')]);
        $member->assignRole(Roles::MEMBER);

        $this->postJson('/api/v1/auth/login', ['email' => 'm@example.test', 'password' => 'secret123', 'device_name' => 'test'])
            ->assertOk()
            ->assertJsonPath('user.gym.branding.theme', 'dark')
            ->assertJsonPath('user.gym.branding.on_primary', '#FFFFFF')
            ->assertJsonPath('user.gym.branding.logo_url', null);

        $this->getJson("/api/v1/join/{$this->gym->join_token}")->assertOk()->assertJsonPath('gym.branding.accent', '#F97316');
    }

    private function followDashboard(User $user): string
    {
        return route($user->dashboardRoute());
    }
}
