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
        Schema::table('user_profiles', function (Blueprint $table) {
            // Notification settings as JSON
            $table->json('notifications')->nullable()->after('photos');

            // Privacy settings as JSON
            $table->json('privacy_settings')->nullable()->after('notifications');

            // Visibility settings as JSON
            $table->json('visibility_settings')->nullable()->after('privacy_settings');

            // Add soft deletes
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'notifications',
                'privacy_settings',
                'visibility_settings',
                'deleted_at'
            ]);
        });
    }
};
