<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('user_profiles', function (Blueprint $table) {
            $table->id();
            $table->uuid('user_id')->unique(); // References auth.users from Supabase
            $table->string('first_name');
            $table->string('last_name');
            $table->string('username')->unique();
            $table->string('phone_number')->nullable();
            $table->date('date_of_birth');
            $table->enum('gender', ['male', 'female', 'non-binary', 'other']);
            $table->enum('sexual_orientation', ['straight', 'gay', 'lesbian', 'bisexual', 'pansexual', 'asexual', 'other']);
            $table->string('location'); // City
            $table->string('state'); // Region/State
            $table->string('country')->default('United Kingdom');
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            
            // Match Preferences
            $table->json('preferred_genders'); // Array of preferred genders
            $table->json('preferred_age_range'); // [min, max]
            $table->integer('preferred_distance')->default(25); // in miles
            $table->enum('relationship_goals', ['casual', 'long-term', 'marriage', 'friendship', 'other']);
            
            // Appearance
            $table->integer('height')->nullable(); // in cm
            $table->enum('body_type', ['slim', 'athletic', 'average', 'curvy', 'plus-size', 'muscular'])->nullable();
            $table->string('ethnicity')->nullable();
            $table->enum('hair_color', ['black', 'brown', 'blonde', 'red', 'gray', 'white', 'other'])->nullable();
            $table->enum('eye_color', ['brown', 'blue', 'green', 'hazel', 'gray', 'other'])->nullable();
            
            // Lifestyle
            $table->enum('education_level', ['high-school', 'some-college', 'bachelors', 'masters', 'phd', 'trade-school', 'other'])->nullable();
            $table->string('occupation')->nullable();
            $table->enum('religion', ['christian', 'muslim', 'jewish', 'hindu', 'buddhist', 'atheist', 'agnostic', 'spiritual', 'other'])->nullable();
            $table->enum('drinking_habits', ['never', 'rarely', 'socially', 'regularly', 'prefer-not-to-say'])->nullable();
            $table->enum('smoking_habits', ['never', 'rarely', 'socially', 'regularly', 'trying-to-quit', 'prefer-not-to-say'])->nullable();
            $table->enum('exercise_frequency', ['never', 'rarely', 'sometimes', 'regularly', 'daily'])->nullable();
            
            // Interests & Personality
            $table->json('interests')->nullable(); // Array of interests
            $table->text('bio')->nullable();
            $table->text('perfect_first_date')->nullable();
            $table->text('favorite_weekend')->nullable();
            $table->text('surprising_fact')->nullable();
            
            // Photos and metadata
            $table->json('photos')->nullable(); // Array of photo objects
            $table->timestamp('registration_date');
            $table->boolean('is_verified')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_active_at')->nullable();
            $table->text('admin_notes')->nullable();
            
            $table->timestamps();
            
            $table->index(['latitude', 'longitude']);
            $table->index('last_active_at');
            $table->index('is_active');
            $table->index('location');
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_profiles');
    }
};