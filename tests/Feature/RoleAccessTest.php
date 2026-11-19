<?php

namespace Tests\Feature;

use App\Models\Gym;
use App\Models\User;
use App\Support\Roles;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    private Gym $gym;
    private Gym $otherGym;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->gym = Gym::create(['name' => 'Alpha', 'slug' => 'alpha']);
        $this->otherGym = Gym::create(['name' => 'Beta', 'slug' => 'beta']);
    }

    private function userWithRole(string $role, ?Gym $gym = null, array $attrs = []): User
    {
        $gymId = $role === Roles::SUPER_ADMIN ? null : optional($gym ?? $this->gym)->id;
        $user = User::factory()->create(array_merge(['gym_id' => $gymId], $attrs));
        $user->assignRole($role);

        return $user;
    }

    public function test_dashboard_redirects_each_role_to_its_own_dashboard()
    {
        foreach (Roles::DASHBOARDS as $role => $routeName) {
            $this->actingAs($this->userWithRole($role))
                ->get('/dashboard')
                ->assertRedirect(route($routeName));
        }
    }

    public function test_roles_cannot_open_other_role_dashboards()
    {
        $member = $this->userWithRole(Roles::MEMBER);
        $this->actingAs($member)->get(route('member.dashboard'))->assertOk();
        $this->actingAs($member)->get(route('trainer.dashboard'))->assertForbidden();
        $this->actingAs($member)->get(route('gym-admin.dashboard'))->assertForbidden();
        $this->actingAs($member)->get(route('super-admin.dashboard'))->assertForbidden();

        $this->actingAs($this->userWithRole(Roles::GYM_ADMIN))->get(route('gym-admin.dashboard'))->assertOk();
        $this->actingAs($this->userWithRole(Roles::TRAINER))->get(route('trainer.dashboard'))->assertOk();
        $this->actingAs($this->userWithRole(Roles::SUPER_ADMIN))->get(route('super-admin.dashboard'))->assertOk();
    }

    public function test_guests_are_sent_to_login()
    {
        $this->get(route('member.dashboard'))->assertRedirect(route('login'));
        $this->get(route('manage.users.index'))->assertRedirect(route('login'));
    }

    public function test_gym_owner_registers_a_new_gym_and_lands_on_its_dashboard()
    {
        \Illuminate\Support\Facades\Storage::fake('local');
        $this->get(route('register'))->assertOk()->assertSee('Register your gym');

        $this->post(route('register'), [
            'gym_name' => 'Power House Pune',
            'name' => 'Owner Person',
            'email' => 'owner@example.test',
            'phone' => '98765 43210',
            'address' => '12 FC Road',
            'city' => 'Pune',
            'logo' => \Illuminate\Http\UploadedFile::fake()->image('logo.png', 300, 300),
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'terms' => '1',
        ])->assertRedirect(route('gym-admin.dashboard'));

        $owner = \App\Models\User::where('email', 'owner@example.test')->firstOrFail();
        $gym = $owner->gym;
        $this->assertAuthenticatedAs($owner);
        $this->assertTrue($owner->hasRole(Roles::GYM_ADMIN));
        $this->assertFalse($owner->hasRole(Roles::SUPER_ADMIN));
        $this->assertSame('Power House Pune', $gym->name);
        $this->assertSame($owner->id, $gym->owner_id);
        $this->assertSame('power-house-pune', $gym->join_token);
        $this->assertSame('+919876543210', $owner->phone);
        \Illuminate\Support\Facades\Storage::disk('local')->assertExists($gym->logo_path);

        $this->get(route('gym-admin.dashboard'))->assertOk();
        $this->get(route('manage.gyms.qr', $gym))->assertOk()->assertSee(route('join.show', 'power-house-pune'));
        $this->get(route('media.gym-logo', $gym))->assertOk();

        // The owner sees only their own gym, not other gyms' QR codes.
        $this->get(route('manage.gyms.qr', $this->gym))->assertForbidden();

        $this->post(route('logout'));
        $this->get(route('join.show', 'power-house-pune'))->assertOk()->assertSee('Join Power House Pune');
    }

    public function test_gym_registration_lists_every_problem_and_accepts_large_logos()
    {
        \Illuminate\Support\Facades\Storage::fake('local');
        $payload = [
            'gym_name' => 'Fit Life Jagatpura', 'name' => 'Owner', 'email' => 'fl@example.test', 'phone' => '9876543299',
            'address' => '3rd Floor, Sirsi Rd', 'city' => 'Jaipur', 'password' => 'secret123', 'password_confirmation' => 'secret999',
            'logo' => \Illuminate\Http\UploadedFile::fake()->create('logo.svg', 20, 'image/svg+xml'),
        ];

        $this->from(route('register'))->post(route('register'), $payload)->assertRedirect(route('register'));
        $this->followingRedirects()->from(route('register'))->post(route('register'), $payload)
            ->assertSee('Please fix these and submit again')
            ->assertSee('The two passwords don&#039;t match.', false)
            ->assertSee('SVG and PDF are not supported')
            ->assertSee('Please tick the box to accept the FlexFit terms.');

        // A 6 MB PNG is fine now (it is resized to 512px anyway).
        $payload['password_confirmation'] = 'secret123';
        $payload['terms'] = '1';
        $payload['logo'] = \Illuminate\Http\UploadedFile::fake()->image('logo.png', 2000, 2000)->size(6000);
        $this->post(route('register'), $payload)->assertRedirect(route('gym-admin.dashboard'));
    }

    public function test_gym_registration_rejects_duplicate_gym_names()
    {
        $this->post(route('register'), [
            'gym_name' => $this->gym->name,
            'name' => 'Someone',
            'email' => 'dup@example.test',
            'phone' => '98765 43211',
            'address' => 'x',
            'city' => 'y',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'terms' => '1',
        ])->assertSessionHasErrors('gym_name');
        $this->assertGuest();
    }

    public function test_login_and_suspended_users_are_rejected()
    {
        $user = $this->userWithRole(Roles::MEMBER);
        $this->post(route('login'), ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('dashboard'));
        $this->post(route('logout'));

        $suspended = $this->userWithRole(Roles::MEMBER, null, ['status' => 'suspended']);
        $this->post(route('login'), ['email' => $suspended->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_suspended_user_with_live_session_is_logged_out()
    {
        $user = $this->userWithRole(Roles::MEMBER, null, ['status' => 'suspended']);
        $this->actingAs($user)->get(route('member.dashboard'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_trainers_and_members_cannot_manage_users_or_gyms()
    {
        foreach ([Roles::TRAINER, Roles::MEMBER] as $role) {
            $user = $this->userWithRole($role);
            $this->actingAs($user)->get(route('manage.users.index'))->assertForbidden();
            $this->actingAs($user)->get(route('manage.gyms.index'))->assertForbidden();
        }
    }

    public function test_gym_admin_sees_only_own_gym_and_cannot_manage_gyms()
    {
        $admin = $this->userWithRole(Roles::GYM_ADMIN);
        $mine = $this->userWithRole(Roles::MEMBER, $this->gym, ['name' => 'Mine Member']);
        $theirs = $this->userWithRole(Roles::MEMBER, $this->otherGym, ['name' => 'Theirs Member']);

        $this->actingAs($admin)->get(route('manage.users.index'))
            ->assertOk()->assertSee('Mine Member')->assertDontSee('Theirs Member');

        $this->actingAs($admin)->get(route('manage.users.edit', $mine))->assertOk();
        $this->actingAs($admin)->get(route('manage.users.edit', $theirs))->assertForbidden();
        $this->actingAs($admin)->delete(route('manage.users.destroy', $theirs))->assertForbidden();
        $this->actingAs($admin)->get(route('manage.gyms.index'))->assertForbidden();
    }

    public function test_gym_admin_cannot_create_admins_and_new_users_land_in_their_gym()
    {
        $admin = $this->userWithRole(Roles::GYM_ADMIN);
        $payload = [
            'name' => 'Escalation', 'email' => 'esc@example.test', 'status' => 'active',
            'password' => 'secret123', 'password_confirmation' => 'secret123',
        ];

        $this->actingAs($admin)->post(route('manage.users.store'), $payload + ['role' => Roles::SUPER_ADMIN])
            ->assertSessionHasErrors('role');
        $this->actingAs($admin)->post(route('manage.users.store'), $payload + ['role' => Roles::GYM_ADMIN])
            ->assertSessionHasErrors('role');
        $this->actingAs($admin)->post(route('manage.users.store'), $payload + ['role' => Roles::TRAINER, 'gym_id' => $this->otherGym->id])
            ->assertSessionHasErrors('gym_id');

        $this->actingAs($admin)->post(route('manage.users.store'), $payload + ['role' => Roles::TRAINER])
            ->assertRedirect(route('manage.users.index'));
        $created = User::where('email', 'esc@example.test')->firstOrFail();
        $this->assertTrue($created->hasRole(Roles::TRAINER));
        $this->assertSame($this->gym->id, $created->gym_id);
    }

    public function test_gym_admin_cannot_manage_other_gym_admins()
    {
        $admin = $this->userWithRole(Roles::GYM_ADMIN);
        $peer = $this->userWithRole(Roles::GYM_ADMIN);
        $this->actingAs($admin)->get(route('manage.users.edit', $peer))->assertForbidden();
    }

    public function test_super_admin_manages_everyone_but_cannot_suspend_or_delete_self()
    {
        $super = $this->userWithRole(Roles::SUPER_ADMIN);
        $admin = $this->userWithRole(Roles::GYM_ADMIN, $this->otherGym);

        $this->actingAs($super)->get(route('manage.gyms.index'))->assertOk();
        $this->actingAs($super)->patch(route('manage.users.status', $admin))->assertRedirect();
        $this->assertSame('suspended', $admin->fresh()->status);

        $this->actingAs($super)->patch(route('manage.users.status', $super))->assertForbidden();
        $this->actingAs($super)->delete(route('manage.users.destroy', $super))->assertForbidden();
    }
}
