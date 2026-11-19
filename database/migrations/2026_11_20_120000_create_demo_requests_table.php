<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** "Book a Demo" requests from the FlexFit marketing site. */
class CreateDemoRequestsTable extends Migration
{
    public function up()
    {
        Schema::create('demo_requests', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('phone', 20);
            $table->string('gym_name');
            $table->string('city', 100)->nullable();
            $table->string('members', 20)->nullable(); // size bracket
            $table->date('preferred_date')->nullable();
            $table->string('preferred_time', 20)->nullable();
            $table->text('message')->nullable();
            $table->enum('status', ['new', 'contacted', 'scheduled', 'converted', 'closed'])->default('new')->index();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('demo_requests');
    }
}
