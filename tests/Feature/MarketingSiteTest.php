<?php

namespace Tests\Feature;

use App\Models\DemoRequest;
use App\Models\Gym;
use App\Models\User;
use App\Support\Roles;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketingSiteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_every_marketing_page_loads_with_register_and_login_links()
    {
        Gym::create(['name' => 'Power House', 'slug' => 'power-house', 'city' => 'Pune']);

        foreach (['flexfit', 'features', 'how-it-works', 'pricing', 'about', 'demo', 'gyms', 'register', 'login'] as $name) {
            $this->get(route($name))->assertOk()->assertSee(route('register'), false)->assertSee(route('login'), false);
        }

        $this->assertSame(url('/flexfit/pricing'), route('pricing'));
        $this->get(route('pricing'))->assertSee('Starter')->assertSee('₹999');
        $this->get(route('flexfit'))->assertSee('Power House');
    }

    public function test_old_urls_redirect()
    {
        // FlexFit pages moved under /flexfit when "/" became the gym website.
        $this->get('/features')->assertRedirect('/flexfit/features');
        $this->get('/pricing')->assertRedirect('/flexfit/pricing');
        $this->get('/book-a-demo')->assertRedirect('/flexfit/book-a-demo');
        // Old single-gym pages point at sections of the gym website.
        $this->get('/services')->assertRedirect('/#programs');
        $this->get('/membership-plans')->assertRedirect('/#pricing');
        $this->get('/trainers')->assertRedirect('/#trainers');
        $this->get('/contact')->assertRedirect('/#contact');
        $this->get('/join')->assertRedirect('/gyms');
    }

    public function test_gym_public_page_shows_branding_qr_and_join_link()
    {
        $gym = Gym::create(['name' => 'Power House', 'slug' => 'power-house', 'address' => '12 FC Road', 'city' => 'Pune', 'is_active' => true]);

        $this->get(route('gyms.show', 'power-house'))
            ->assertOk()
            ->assertSee('Power House')
            ->assertSee('12 FC Road, Pune')
            ->assertSee('<svg', false)
            ->assertSee($gym->joinUrl(), false);

        $this->get(route('gyms', ['q' => 'pune']))->assertSee('Power House');
        $this->get(route('gyms', ['q' => 'delhi']))->assertDontSee('Power House');

        $gym->update(['is_active' => false]);
        $this->get(route('gyms.show', 'power-house'))->assertNotFound();
    }

    public function test_demo_request_is_stored_and_managed_by_super_admin()
    {
        $this->post(route('demo.store'), [
            'name' => 'Asha', 'email' => 'asha@example.test', 'phone' => '9876543210', 'gym_name' => 'Asha Fitness',
            'city' => 'Pune', 'members' => '100-300', 'preferred_time' => 'evening',
        ])->assertRedirect(route('demo'));
        $this->get(route('demo'))->assertSee('Request received');

        $demo = DemoRequest::firstOrFail();
        $this->assertSame('new', $demo->status);

        // Honeypot submissions are dropped silently.
        $this->post(route('demo.store'), ['website' => 'spam', 'name' => 'Bot']);
        $this->assertSame(1, DemoRequest::count());

        $this->post(route('demo.store'), ['name' => 'x'])->assertSessionHasErrors(['email', 'phone', 'gym_name']);

        $super = User::factory()->create();
        $super->assignRole(Roles::SUPER_ADMIN);
        $this->actingAs($super)->get(route('super-admin.demos.index'))->assertOk()->assertSee('Asha Fitness');
        $this->patch(route('super-admin.demos.update', $demo), ['status' => 'scheduled'])->assertRedirect();
        $this->assertSame('scheduled', $demo->fresh()->status);

        $owner = User::factory()->create();
        $owner->assignRole(Roles::GYM_ADMIN);
        $this->actingAs($owner)->get(route('super-admin.demos.index'))->assertForbidden();
    }
}
