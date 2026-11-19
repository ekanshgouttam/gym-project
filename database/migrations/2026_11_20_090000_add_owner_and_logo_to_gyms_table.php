<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Self-registered gyms (FlexFit SaaS): who owns the gym, and its logo. */
class AddOwnerAndLogoToGymsTable extends Migration
{
    public function up()
    {
        Schema::table('gyms', function (Blueprint $table) {
            $table->foreignId('owner_id')->nullable()->after('id')->constrained('users')->nullOnDelete();
            $table->string('logo_path')->nullable()->after('city');
        });
    }

    public function down()
    {
        Schema::table('gyms', function (Blueprint $table) {
            $table->dropConstrainedForeignId('owner_id');
            $table->dropColumn('logo_path');
        });
    }
}
