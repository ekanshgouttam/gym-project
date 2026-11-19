<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * FlexFit platform (Super Admin): what gyms pay FlexFit, AI spend, support and settings.
 * Separate from membership_plans/payments, which are what members pay their gym.
 */
class CreatePlatformTables extends Migration
{
    public function up()
    {
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->unsignedInteger('price_monthly');          // INR
            $table->unsignedInteger('price_yearly')->nullable(); // INR
            $table->unsignedInteger('member_limit')->nullable(); // null = unlimited
            $table->unsignedInteger('ai_scans_per_month')->nullable();
            $table->json('features')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('gym_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gym_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subscription_plan_id')->constrained()->restrictOnDelete();
            $table->enum('status', ['trial', 'active', 'past_due', 'cancelled', 'expired'])->default('trial')->index();
            $table->enum('billing_cycle', ['monthly', 'yearly'])->default('monthly');
            $table->unsignedInteger('amount'); // INR per cycle
            $table->date('starts_on');
            $table->date('ends_on')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
        });

        Schema::create('subscription_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gym_id')->constrained()->cascadeOnDelete();
            $table->foreignId('gym_subscription_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('amount'); // INR
            $table->string('method', 20)->default('manual'); // razorpay, upi, bank, cash, manual
            $table->string('reference')->nullable();
            $table->enum('status', ['paid', 'pending', 'failed', 'refunded'])->default('paid')->index();
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            $table->timestamp('paid_at')->nullable()->index();
            $table->string('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('ai_usage_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gym_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('kind', 30); // scan_analysis, scan_comparison
            $table->string('model', 60)->nullable();
            $table->unsignedInteger('input_tokens')->default(0);
            $table->unsignedInteger('output_tokens')->default(0);
            $table->unsignedInteger('cache_write_tokens')->default(0);
            $table->unsignedInteger('cache_read_tokens')->default(0);
            $table->decimal('cost_usd', 10, 6)->default(0);
            $table->boolean('success')->default(true);
            $table->string('error')->nullable();
            $table->timestamp('created_at')->nullable()->index();
        });

        Schema::create('support_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gym_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('subject');
            $table->text('message');
            $table->string('category', 30)->default('general'); // general, billing, technical, ai_scans, feature
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal');
            $table->enum('status', ['open', 'in_progress', 'resolved', 'closed'])->default('open')->index();
            $table->text('admin_reply')->nullable();
            $table->timestamp('replied_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });

        Schema::create('platform_settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        $now = now();
        DB::table('subscription_plans')->insert([
            ['name' => 'Starter', 'slug' => 'starter', 'price_monthly' => 999, 'price_yearly' => 9990, 'member_limit' => 150, 'ai_scans_per_month' => 100,
                'features' => json_encode(['QR member registration', 'Attendance & fees', 'Member app']), 'is_active' => true, 'sort_order' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Growth', 'slug' => 'growth', 'price_monthly' => 2499, 'price_yearly' => 24990, 'member_limit' => 600, 'ai_scans_per_month' => 500,
                'features' => json_encode(['Everything in Starter', 'Trainers, workout & diet plans', 'Reports & reminders']), 'is_active' => true, 'sort_order' => 2, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Pro', 'slug' => 'pro', 'price_monthly' => 4999, 'price_yearly' => 49990, 'member_limit' => null, 'ai_scans_per_month' => 2000,
                'features' => json_encode(['Everything in Growth', 'Unlimited members', 'Priority support']), 'is_active' => true, 'sort_order' => 3, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down()
    {
        foreach (['platform_settings', 'support_requests', 'ai_usage_logs', 'subscription_payments', 'gym_subscriptions', 'subscription_plans'] as $t) {
            Schema::dropIfExists($t);
        }
    }
}
