<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('blocked_users', function (Blueprint $table) {
            $table->id();
            $table->uuid('blocker_id'); // User who blocked
            $table->uuid('blocked_id'); // User who was blocked
            $table->text('reason')->nullable();
            $table->timestamps();
            
            $table->unique(['blocker_id', 'blocked_id']);
            $table->index('blocker_id');
            $table->index('blocked_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('blocked_users');
    }
};