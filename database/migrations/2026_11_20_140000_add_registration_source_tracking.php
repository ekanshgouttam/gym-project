<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Where each member came from (QR scan, shared link, added at the desk, CSV import),
 * and visits to each gym's join page, so owners can see QR scans and conversions.
 */
class AddRegistrationSourceTracking extends Migration
{
    public function up()
    {
        Schema::table('member_profiles', function (Blueprint $table) {
            $table->string('source', 10)->default('link')->after('member_code')->index(); // qr, link, manual, import
            $table->foreignId('registered_by')->nullable()->after('source')->constrained('users')->nullOnDelete();
        });

        Schema::create('join_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gym_id')->constrained()->cascadeOnDelete();
            $table->string('source', 10); // qr, link
            $table->timestamp('created_at')->nullable()->index();
        });
    }

    public function down()
    {
        Schema::dropIfExists('join_visits');
        Schema::table('member_profiles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('registered_by');
            $table->dropColumn('source');
        });
    }
}
