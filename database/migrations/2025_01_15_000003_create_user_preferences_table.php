<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('user_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->integer('min_age')->default(18);
            $table->integer('max_age')->default(65);
            $table->integer('max_distance')->default(25); // in miles
            $table->json('interested_genders')->nullable();
            $table->integer('min_height')->nullable();
            $table->integer('max_height')->nullable();
            $table->json('education_preferences')->nullable();
            $table->json('profession_preferences')->nullable();
            $table->boolean('show_age')->default(true);
            $table->boolean('show_distance')->default(true);
            $table->boolean('show_online_status')->default(true);
            $table->boolean('show_read_receipts')->default(true);
            $table->timestamps();
            
            $table->unique('user_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_preferences');
    }
};