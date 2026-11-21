<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Self-registration from the mobile app: the person gets an "applicant" account and a request
 * to join a gym, which the gym admin approves or rejects. Requests are never deleted, so
 * rejected and still-waiting applicants stay on record.
 */
class CreateRegistrationRequestsTable extends Migration
{
    public function up()
    {
        DB::statement("ALTER TABLE users MODIFY status ENUM('active', 'suspended', 'applicant') NOT NULL DEFAULT 'active'");

        Schema::create('registration_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('gym_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->string('source', 10)->default('app'); // app = picked from the list, qr = scanned the gym's QR code
            $table->string('rejection_reason', 200)->nullable();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();
            $table->index(['gym_id', 'status']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('registration_requests');
        DB::statement("UPDATE users SET status = 'suspended' WHERE status = 'applicant'");
        DB::statement("ALTER TABLE users MODIFY status ENUM('active', 'suspended') NOT NULL DEFAULT 'active'");
    }
}
