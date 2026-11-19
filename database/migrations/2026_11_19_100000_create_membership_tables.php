<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMembershipTables extends Migration
{
    public function up()
    {
        Schema::table('gyms', function (Blueprint $table) {
            // Secret token baked into the branch's QR code (rotatable without changing the slug).
            $table->string('join_token', 40)->nullable()->unique()->after('slug');
        });

        Schema::create('membership_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gym_id')->nullable()->constrained()->cascadeOnDelete(); // null = all branches
            $table->string('name');
            $table->string('slug');
            $table->unsignedSmallInteger('duration_months');
            $table->decimal('price', 10, 2);
            $table->string('description')->nullable();
            $table->json('features')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('member_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('member_code', 30)->unique();
            $table->date('date_of_birth')->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->decimal('height_cm', 5, 1)->nullable();
            $table->decimal('starting_weight_kg', 5, 1)->nullable();
            $table->decimal('target_weight_kg', 5, 1)->nullable();
            $table->string('fitness_goal', 40)->nullable();
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone', 20)->nullable();
            $table->timestamp('phone_verified_at')->nullable();
            $table->json('notification_prefs')->nullable();
            $table->timestamps();
        });

        Schema::create('memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('gym_id')->constrained()->cascadeOnDelete();
            $table->foreignId('membership_plan_id')->constrained()->restrictOnDelete();
            $table->enum('status', ['pending', 'active', 'expired', 'cancelled', 'rejected'])->default('pending')->index();
            $table->decimal('amount', 10, 2);
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->enum('activated_via', ['payment', 'admin'])->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('membership_id')->constrained()->cascadeOnDelete();
            $table->string('gateway', 20)->default('razorpay');
            $table->string('gateway_order_id')->unique();
            $table->string('gateway_payment_id')->nullable()->unique();
            $table->unsignedInteger('amount_paise');
            $table->string('currency', 3)->default('INR');
            $table->enum('status', ['created', 'paid', 'failed'])->default('created');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });

        Schema::create('otp_verifications', function (Blueprint $table) {
            $table->id();
            $table->string('phone', 20)->index();
            $table->string('purpose', 30);
            $table->string('code_hash');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('expires_at');
            $table->timestamp('verified_at')->nullable();
            $table->ipAddress('ip')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('otp_verifications');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('memberships');
        Schema::dropIfExists('member_profiles');
        Schema::dropIfExists('membership_plans');
        Schema::table('gyms', function (Blueprint $table) {
            $table->dropColumn('join_token');
        });
    }
}
