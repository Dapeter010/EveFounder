<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('profile_boosts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('boost_type', ['profile', 'super', 'weekend']);
            $table->decimal('cost', 8, 2);
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->integer('views_gained')->default(0);
            $table->integer('likes_gained')->default(0);
            $table->integer('matches_gained')->default(0);
            $table->enum('status', ['active', 'completed', 'cancelled'])->default('active');
            $table->timestamps();
            
            $table->index(['user_id', 'status']);
            $table->index(['starts_at', 'ends_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('profile_boosts');
    }
};