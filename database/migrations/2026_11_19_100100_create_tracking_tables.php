<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTrackingTables extends Migration
{
    public function up()
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('gym_id')->constrained()->cascadeOnDelete();
            $table->timestamp('checked_in_at')->index();
            $table->timestamp('checked_out_at')->nullable();
            $table->string('method', 20)->default('qr');
            $table->timestamps();
        });

        Schema::create('body_scans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('front_path');
            $table->string('side_path');
            $table->string('back_path')->nullable();
            $table->decimal('weight_kg', 5, 1)->nullable();
            $table->enum('status', ['pending', 'completed', 'failed'])->default('pending');
            $table->decimal('body_fat_estimate', 4, 1)->nullable();
            $table->unsignedTinyInteger('physique_score')->nullable();
            $table->text('summary')->nullable();
            $table->json('analysis')->nullable();
            $table->string('error')->nullable();
            $table->timestamp('analyzed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('weight_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('logged_on');
            $table->decimal('weight_kg', 5, 1);
            $table->string('note')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'logged_on']);
        });

        Schema::create('body_measurements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('measured_on');
            foreach (['chest', 'waist', 'hips', 'arms', 'thighs', 'neck'] as $part) {
                $table->decimal($part . '_cm', 5, 1)->nullable();
            }
            $table->decimal('body_fat_pct', 4, 1)->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'measured_on']);
        });

        Schema::create('workout_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('trainer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->string('goal')->nullable();
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->json('days'); // [{day, focus, exercises: [{name, sets, reps, rest}]}]
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('diet_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('trainer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->unsignedSmallInteger('daily_calories')->nullable();
            $table->json('macros')->nullable(); // {protein, carbs, fat} grams
            $table->json('meals');              // [{time, name, items: []}]
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('trainer_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('specialty')->nullable();
            $table->text('bio')->nullable();
            $table->unsignedTinyInteger('experience_years')->default(0);
            $table->string('certifications')->nullable();
            $table->boolean('is_public')->default(true);
            $table->timestamps();
        });

        Schema::create('contact_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gym_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('email');
            $table->string('phone', 20)->nullable();
            $table->string('subject')->nullable();
            $table->text('message');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        foreach (['contact_messages', 'trainer_profiles', 'diet_plans', 'workout_plans', 'body_measurements', 'weight_logs', 'body_scans', 'attendances'] as $t) {
            Schema::dropIfExists($t);
        }
    }
}
