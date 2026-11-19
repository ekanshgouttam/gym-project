<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddProfileFieldsToUsersTable extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('gym_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->string('phone', 20)->nullable()->after('email');
            $table->string('avatar_path')->nullable()->after('phone');
            $table->enum('status', ['active', 'suspended'])->default('active')->after('avatar_path');
            $table->timestamp('last_login_at')->nullable()->after('remember_token');
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('gym_id');
            $table->dropColumn(['phone', 'avatar_path', 'status', 'last_login_at']);
        });
    }
}
