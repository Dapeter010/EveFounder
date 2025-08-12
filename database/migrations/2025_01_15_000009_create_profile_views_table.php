<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('profile_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('viewer_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('viewed_id')->constrained('users')->onDelete('cascade');
            $table->timestamp('viewed_at');
            $table->timestamps();
            
            $table->unique(['viewer_id', 'viewed_id', 'viewed_at']);
            $table->index(['viewed_id', 'viewed_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('profile_views');
    }
};