<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('liker_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('liked_id')->constrained('users')->onDelete('cascade');
            $table->boolean('is_super_like')->default(false);
            $table->enum('status', ['pending', 'matched', 'expired'])->default('pending');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            
            $table->unique(['liker_id', 'liked_id']);
            $table->index(['liked_id', 'status']);
            $table->index('expires_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('likes');
    }
};