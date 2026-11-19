<?php

namespace Tests\Feature;

use App\Models\Enquiry;
use App\Models\Gym;
use App\Models\MembershipPlan;
use App\Models\PlatformSetting;
use App\Models\TrainerProfile;
use App\Models\User;
use App\Support\Roles;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** The premium gym website at "/". */
class GymWebsiteTest extends TestCase
{
    use RefreshDatabase;

    private Gym $gym;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->gym = Gym::create(['name' => 'Iron Temple Jaipur', 'slug' => 'iron-temple-jaipur', 'is_active' => true,
            'address' => 'Sirsi Rd', 'city' => 'Jaipur', 'phone' => '+91 98290 00000', 'email' => 'hello@irontemple.test',
            'settings' => ['opening_hours' => 'Mon–Sat 5 am – 11 pm']]);
    }

    public function test_homepage_shows_every_section_with_the_gyms_own_data()
    {
        $coach = User::factory()->create(['gym_id' => $this->gym->id, 'name' => 'Vikram Rathore']);
        $coach->assignRole(Roles::TRAINER);
        TrainerProfile::create(['user_id' => $coach->id, 'specialty' => 'Powerlifting', 'experience_years' => 9, 'is_public' => true]);
        $hidden = User::factory()->create(['gym_id' => $this->gym->id, 'name' => 'Hidden Coach']);
        $hidden->assignRole(Roles::TRAINER);
        TrainerProfile::create(['user_id' => $hidden->id, 'is_public' => false]);
        MembershipPlan::create(['gym_id' => $this->gym->id, 'name' => 'Quarterly Pro', 'slug' => 'qp', 'duration_months' => 3, 'price' => 5999, 'features' => ['Open gym']]);

        $res = $this->get('/')->assertOk();
        foreach (['id="home"', 'id="about"', 'id="forge"', 'id="programs"', 'id="training"', 'id="trainers"', 'id="pricing"', 'id="results"', 'id="gallery"', 'id="testimonials"', 'id="contact"', 'id="join"'] as $section) {
            $res->assertSee($section, false);
        }
        $res->assertSee('BUILD YOUR')
            ->assertSee('Iron Temple Jaipur — Premium Gym &amp; Personal Training in Jaipur', false)
            ->assertSee('"@type":"HealthClub"', false)
            ->assertSee('Vikram Rathore')->assertSee('Powerlifting')
            ->assertDontSee('Hidden Coach')
            ->assertSee('Quarterly Pro')->assertSee('₹5,999')
            ->assertSee('Mon–Sat 5 am – 11 pm')
            ->assertSee($this->gym->joinUrl(), false)
            ->assertSee('<svg', false)                       // QR code
            ->assertSee('https://wa.me/919829000000', false)  // WhatsApp button
            ->assertSee('media/site/hero.mp4', false);
    }

    public function test_trial_and_contact_forms_become_enquiries_for_the_gym_owner()
    {
        $this->post(route('site.enquire'), ['form' => 'join', 'name' => 'Neha Singh', 'phone' => '98290 12345', 'interest' => 'Boxing', 'preferred_time' => 'evening'])
            ->assertRedirect(route('home') . '#join');
        $trial = Enquiry::where('name', 'Neha Singh')->firstOrFail();
        $this->assertSame($this->gym->id, $trial->gym_id);
        $this->assertSame('website', $trial->source);
        $this->assertSame('trial', $trial->status);
        $this->assertSame('+919829012345', $trial->phone);
        $this->assertTrue($trial->next_follow_up_on->isToday());
        $this->assertStringContainsString('evening', $trial->notes);
        $this->get('/')->assertSee('See you soon')->assertSee("You're booked, Neha!");

        $this->post(route('site.enquire'), ['form' => 'contact', 'name' => 'Amit', 'phone' => '9829011111', 'message' => 'Do you have parking?'])
            ->assertRedirect(route('home') . '#contact');
        $this->assertSame('new', Enquiry::where('name', 'Amit')->value('status'));

        // Bad input goes back to the form's own section, with errors only on that form.
        $this->post(route('site.enquire'), ['form' => 'join', 'name' => 'X', 'phone' => '123'])
            ->assertRedirect(route('home') . '#join')
            ->assertSessionHasErrorsIn('join', 'phone');
        $this->post(route('site.enquire'), ['form' => 'contact'])->assertSessionHasErrorsIn('contact', ['name', 'phone']);

        // Honeypot: bots are thanked and ignored.
        $this->post(route('site.enquire'), ['form' => 'join', 'company' => 'spam', 'name' => 'Bot', 'phone' => '9829099999']);
        $this->assertSame(2, Enquiry::count());
    }

    public function test_super_admin_chooses_the_website_gym_and_owner_uploads_media()
    {
        Storage::fake('local');
        $other = Gym::create(['name' => 'Muscle Factory', 'slug' => 'muscle-factory', 'is_active' => true, 'city' => 'Pune']);
        $this->get('/')->assertSee('Iron Temple Jaipur');

        PlatformSetting::put(['site_gym' => $other->id]);
        $this->get('/')->assertSee('Muscle Factory')->assertDontSee('Iron Temple Jaipur');

        $owner = User::factory()->create(['gym_id' => $other->id]);
        $owner->assignRole(Roles::GYM_ADMIN);
        $this->actingAs($owner)->put(route('gym-admin.settings.update'), [
            'name' => 'Muscle Factory',
            'hero_image' => UploadedFile::fake()->image('hero.jpg', 2400, 1600),
            'hero_video' => UploadedFile::fake()->create('hero.mp4', 2048, 'video/mp4'),
            'gallery' => [UploadedFile::fake()->image('a.png', 800, 600), UploadedFile::fake()->image('b.jpg', 800, 600)],
        ])->assertRedirect();

        $media = $other->fresh()->setting('site_media');
        $this->assertCount(2, $media['gallery']);
        $this->assertStringEndsWith('.webp', $media['hero_image']);
        Storage::disk('local')->assertExists("site/{$other->id}/{$media['hero_image']}");
        [$w] = getimagesizefromstring(Storage::disk('local')->get("site/{$other->id}/{$media['hero_image']}"));
        $this->assertSame(1920, $w); // resized

        $this->post(route('logout'));
        $this->get('/')->assertSee("site-media/{$other->id}/{$media['hero_video']}", false)->assertSee("site-media/{$other->id}/{$media['gallery'][0]}", false);
        $this->get(route('site.media', [$other, $media['gallery'][0]]))->assertOk()->assertHeader('content-type', 'image/webp');
        $this->get("/site-media/{$other->id}/../../.env")->assertNotFound();

        // Removing a gallery photo deletes the file.
        $this->actingAs($owner)->put(route('gym-admin.settings.update'), ['name' => 'Muscle Factory', 'remove_gallery' => [$media['gallery'][0]]]);
        $this->assertCount(1, $other->fresh()->setting('site_media')['gallery']);
        Storage::disk('local')->assertMissing("site/{$other->id}/{$media['gallery'][0]}");
    }

    public function test_homepage_never_404s_while_a_gym_exists_and_flexfit_logo_points_to_flexfit()
    {
        PlatformSetting::put(['site_gym' => 999]); // stale id falls back to the first active gym
        $this->get('/')->assertOk()->assertSee('Iron Temple Jaipur');
        $this->get(route('login'))->assertSee('href="' . route('flexfit') . '"', false);
    }
}
