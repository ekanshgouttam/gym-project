<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Gym owner panel: enquiries, desk payments, trainer assignment, gym settings and reminder log. */
class CreateGymAdminModules extends Migration
{
    public function up()
    {
        Schema::create('enquiries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gym_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('phone', 20);
            $table->string('email')->nullable();
            $table->string('source', 20)->default('walk_in');
            $table->string('interest')->nullable();
            $table->enum('status', ['new', 'follow_up', 'trial', 'converted', 'lost'])->default('new')->index();
            $table->date('next_follow_up_on')->nullable()->index();
            $table->text('notes')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('converted_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('converted_at')->nullable();
            $table->string('lost_reason')->nullable();
            $table->timestamps();
        });

        Schema::create('enquiry_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enquiry_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('note');
            $table->timestamp('created_at')->nullable();
        });

        // Payments taken at the desk (cash, UPI, card) live alongside Razorpay ones.
        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('recorded_by')->nullable()->after('paid_at')->constrained('users')->nullOnDelete();
            $table->string('note')->nullable()->after('recorded_by');
        });

        Schema::table('member_profiles', function (Blueprint $table) {
            $table->foreignId('trainer_id')->nullable()->after('registered_by')->constrained('users')->nullOnDelete();
        });

        Schema::table('gyms', function (Blueprint $table) {
            $table->json('settings')->nullable()->after('logo_path');
        });

        Schema::create('membership_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('membership_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 30); // expiring_7, expiring_3, expiring_1, expired, payment_due
            $table->timestamp('created_at')->nullable();
            $table->unique(['membership_id', 'kind']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('membership_reminders');
        Schema::table('gyms', function (Blueprint $table) { $table->dropColumn('settings'); });
        Schema::table('member_profiles', function (Blueprint $table) { $table->dropConstrainedForeignId('trainer_id'); });
        Schema::table('payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('recorded_by');
            $table->dropColumn('note');
        });
        Schema::dropIfExists('enquiry_activities');
        Schema::dropIfExists('enquiries');
    }
}
