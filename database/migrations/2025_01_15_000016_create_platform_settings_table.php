<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('platform_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value');
            $table->string('type')->default('string'); // string, integer, boolean, json
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Insert default settings
        DB::table('platform_settings')->insert([
            ['key' => 'platform.minAge', 'value' => '18', 'type' => 'integer', 'description' => 'Minimum age for users'],
            ['key' => 'platform.maxAge', 'value' => '65', 'type' => 'integer', 'description' => 'Maximum age for users'],
            ['key' => 'platform.maxDistance', 'value' => '100', 'type' => 'integer', 'description' => 'Maximum distance in miles'],
            ['key' => 'platform.dailySuperLikes', 'value' => '5', 'type' => 'integer', 'description' => 'Daily super likes for free users'],
            ['key' => 'platform.maxPhotos', 'value' => '6', 'type' => 'integer', 'description' => 'Maximum photos per profile'],
            ['key' => 'platform.bioMaxLength', 'value' => '500', 'type' => 'integer', 'description' => 'Maximum bio length'],
            ['key' => 'platform.maintenanceMode', 'value' => 'false', 'type' => 'boolean', 'description' => 'Maintenance mode status'],
            ['key' => 'platform.newRegistrations', 'value' => 'true', 'type' => 'boolean', 'description' => 'Allow new registrations'],
            
            ['key' => 'matching.aiMatching', 'value' => 'true', 'type' => 'boolean', 'description' => 'Enable AI matching'],
            ['key' => 'matching.compatibilityThreshold', 'value' => '60', 'type' => 'integer', 'description' => 'Minimum compatibility score'],
            ['key' => 'matching.boostDuration', 'value' => '30', 'type' => 'integer', 'description' => 'Boost duration in minutes'],
            ['key' => 'matching.matchExpiry', 'value' => '30', 'type' => 'integer', 'description' => 'Match expiry in days'],
            ['key' => 'matching.autoHideInactive', 'value' => 'true', 'type' => 'boolean', 'description' => 'Auto hide inactive users'],
            ['key' => 'matching.inactiveDays', 'value' => '30', 'type' => 'integer', 'description' => 'Days before user considered inactive'],
            
            ['key' => 'safety.photoVerification', 'value' => 'true', 'type' => 'boolean', 'description' => 'Require photo verification'],
            ['key' => 'safety.autoModeration', 'value' => 'true', 'type' => 'boolean', 'description' => 'Enable auto moderation'],
            ['key' => 'safety.reportThreshold', 'value' => '3', 'type' => 'integer', 'description' => 'Reports before auto-suspend'],
            ['key' => 'safety.autoSuspend', 'value' => 'true', 'type' => 'boolean', 'description' => 'Auto suspend users'],
            ['key' => 'safety.requirePhoneVerification', 'value' => 'false', 'type' => 'boolean', 'description' => 'Require phone verification'],
            ['key' => 'safety.allowVideoChat', 'value' => 'true', 'type' => 'boolean', 'description' => 'Allow video chat'],
            
            ['key' => 'billing.basicPrice', 'value' => '9.99', 'type' => 'float', 'description' => 'Basic plan price'],
            ['key' => 'billing.premiumPrice', 'value' => '19.99', 'type' => 'float', 'description' => 'Premium plan price'],
            ['key' => 'billing.boostPrice', 'value' => '4.99', 'type' => 'float', 'description' => 'Profile boost price'],
            ['key' => 'billing.superBoostPrice', 'value' => '9.99', 'type' => 'float', 'description' => 'Super boost price'],
            ['key' => 'billing.weekendBoostPrice', 'value' => '14.99', 'type' => 'float', 'description' => 'Weekend boost price'],
            ['key' => 'billing.currency', 'value' => 'GBP', 'type' => 'string', 'description' => 'Default currency'],
            ['key' => 'billing.taxRate', 'value' => '20', 'type' => 'integer', 'description' => 'Tax rate percentage'],
        ]);
    }

    public function down()
    {
        Schema::dropIfExists('platform_settings');
    }
};