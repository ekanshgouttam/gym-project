<?php

namespace Tests\Feature;

use App\Models\Gym;
use App\Models\MembershipPlan;
use App\Models\User;
use App\Support\Roles;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MemberIntakeTest extends TestCase
{
    use RefreshDatabase;

    private Gym $gym;
    private User $owner;
    private MembershipPlan $plan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        Storage::fake('local');
        $this->gym = Gym::create(['name' => 'Alpha', 'slug' => 'alpha']);
        $this->owner = User::factory()->create(['gym_id' => $this->gym->id]);
        $this->owner->assignRole(Roles::GYM_ADMIN);
        $this->plan = MembershipPlan::create(['gym_id' => $this->gym->id, 'name' => 'Monthly', 'slug' => 'monthly', 'duration_months' => 1, 'price' => 1500, 'features' => []]);
    }

    public function test_owner_registers_a_member_at_the_desk_with_an_active_plan()
    {
        $this->actingAs($this->owner)->get(route('manage.members.create'))->assertOk();

        $this->post(route('manage.members.store'), [
            'name' => 'Desk Member', 'email' => 'desk@example.test', 'phone' => '98200 11111',
            'gender' => 'female', 'membership_plan_id' => $this->plan->id, 'payment' => 'paid',
        ])->assertRedirect(route('manage.users.index', ['role' => 'member']));

        $member = User::where('email', 'desk@example.test')->firstOrFail();
        $this->assertTrue($member->hasRole(Roles::MEMBER));
        $this->assertSame($this->gym->id, $member->gym_id);
        $this->assertSame('+919820011111', $member->phone);
        $this->assertSame('manual', $member->memberProfile->source);
        $this->assertSame($this->owner->id, $member->memberProfile->registered_by);
        $this->assertStringStartsWith('IF-', $member->memberProfile->member_code);
        $this->assertTrue($member->hasActiveMembership());
        $this->assertSame(2, $member->notifications()->count()); // welcome + membership activated

        // Same mobile again is refused; unpaid plan stays pending.
        $this->post(route('manage.members.store'), ['name' => 'X', 'email' => 'x@example.test', 'phone' => '9820011111'])->assertSessionHasErrors('phone');
        $this->post(route('manage.members.store'), [
            'name' => 'Later', 'email' => 'later@example.test', 'phone' => '9820022222', 'membership_plan_id' => $this->plan->id, 'payment' => 'pending',
        ]);
        $later = User::where('email', 'later@example.test')->firstOrFail();
        $this->assertFalse($later->hasActiveMembership());
        $this->assertSame('pending', $later->memberships()->first()->status);
    }

    public function test_csv_import_previews_rows_then_imports_only_valid_ones()
    {
        User::factory()->create(['email' => 'taken@example.test', 'phone' => '+919820033333']);

        $csv = implode("\n", [
            "\xEF\xBB\xBFName,Email,Phone,Date of birth,Gender,Plan,Paid",
            'Priya Sharma,priya@example.test,9820012345,1995-04-12,F,monthly,yes',
            'Rahul Verma,rahul@example.test,+91 98200 54321,22/08/1990,male,,',
            'No Email,,9820099999,,,,',
            'Dup Phone,dup@example.test,9820012345,,,,',
            'Existing,taken@example.test,9820033333,,,,',
            'Bad Plan,plan@example.test,9820044444,,,Platinum,yes',
            '',
        ]);
        $file = UploadedFile::fake()->createWithContent('members.csv', $csv);

        $this->actingAs($this->owner)->post(route('manage.members.import.preview'), ['file' => $file])
            ->assertOk()
            ->assertSee('Ready to import')
            ->assertSee('Email is missing or invalid')
            ->assertSee('Same mobile appears twice in the file')
            ->assertSee('Email already has an account')
            ->assertSee('is not offered at this gym');
        $this->assertSame(2, User::count()); // nothing imported yet: just the owner and the existing user

        $this->post(route('manage.members.import.run'), ['notify' => '1'])->assertRedirect();

        $priya = User::where('email', 'priya@example.test')->firstOrFail();
        $this->assertSame('import', $priya->memberProfile->source);
        $this->assertSame('female', $priya->memberProfile->gender);
        $this->assertTrue($priya->hasActiveMembership());
        $rahul = User::where('email', 'rahul@example.test')->firstOrFail();
        $this->assertSame('1990-08-22', $rahul->memberProfile->date_of_birth->toDateString());
        $this->assertFalse($rahul->hasActiveMembership());
        $this->assertSame(2, User::role(Roles::MEMBER)->count());

        // The preview can't be imported twice.
        $this->post(route('manage.members.import.run'))->assertRedirect(route('manage.members.import'));
        $this->assertSame(2, User::role(Roles::MEMBER)->count());
        $this->assertEmpty(Storage::disk('local')->files('imports'));
    }

    public function test_import_rejects_files_without_required_columns_and_template_downloads()
    {
        $this->actingAs($this->owner)->get(route('manage.members.import.template'))->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $file = UploadedFile::fake()->createWithContent('members.csv', "fullname,mail\nA,b@example.test\n");
        $this->post(route('manage.members.import.preview'), ['file' => $file])->assertSessionHasErrors('file');
    }

    public function test_qr_page_shows_tracking_and_trainers_cannot_add_members()
    {
        $this->actingAs($this->owner)->get(route('manage.gyms.qr', $this->gym))->assertOk()->assertSee('QR registrations');

        $trainer = User::factory()->create(['gym_id' => $this->gym->id]);
        $trainer->assignRole(Roles::TRAINER);
        $this->actingAs($trainer)->get(route('manage.members.create'))->assertForbidden();
        $this->actingAs($trainer)->get(route('manage.members.import'))->assertForbidden();
    }
}
