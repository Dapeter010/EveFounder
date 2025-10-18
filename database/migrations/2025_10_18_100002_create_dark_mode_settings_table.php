<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('dark_mode_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            // Privacy Features
            $table->boolean('invisible_mode')->default(false)->comment('Hide profile from discovery without unmatching');
            $table->boolean('ghost_mode')->default(false)->comment('View others without appearing in Recently Active');

            // Location Obfuscation
            $table->boolean('location_obfuscation_enabled')->default(false);
            $table->integer('location_obfuscation_radius')->default(5)->comment('Radius in kilometers');

            // Screenshot Prevention (client-side enforcement)
            $table->boolean('screenshot_prevention')->default(false);

            // Auto-Delete Messages
            $table->boolean('auto_delete_messages')->default(false);
            $table->integer('auto_delete_delay')->default(30)->comment('Delay in seconds after reading');

            $table->timestamps();

            // Ensure one settings record per user
            $table->unique('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dark_mode_settings');
    }
};
