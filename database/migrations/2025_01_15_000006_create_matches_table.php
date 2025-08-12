<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user1_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('user2_id')->constrained('users')->onDelete('cascade');
            $table->timestamp('matched_at');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->unique(['user1_id', 'user2_id']);
            $table->index(['user1_id', 'is_active']);
            $table->index(['user2_id', 'is_active']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('matches');
    }
};