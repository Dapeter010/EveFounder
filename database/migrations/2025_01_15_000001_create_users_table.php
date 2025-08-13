<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('first_name');
            $table->string('last_name');
            $table->date('date_of_birth');
            $table->enum('gender', ['male', 'female', 'non-binary', 'prefer-not-to-say']);
            $table->string('location');
            $table->text('bio')->nullable();
            $table->json('interests')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_admin')->default(false);
            $table->timestamp('last_active_at')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->integer('height')->nullable(); // in cm
            $table->string('education')->nullable();
            $table->string('profession')->nullable();
            $table->enum('relationship_type', ['casual', 'serious-relationship', 'marriage', 'friends'])->nullable();
            $table->text('admin_notes')->nullable();
            $table->rememberToken();
            $table->timestamps();

            $table->index(['latitude', 'longitude']);
            $table->index('last_active_at');
            $table->index('is_active');
        });
    }

    public function down()
    {
        Schema::dropIfExists('users');
    }
};
