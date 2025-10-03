<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\UserProfile;
use App\Models\UserPhoto;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Migrate photos from user_profiles.photos JSON column to user_photos table
     */
    public function up(): void
    {
        // Get all user profiles with photos
        $userProfiles = DB::table('user_profiles')
            ->whereNotNull('photos')
            ->where('photos', '!=', '[]')
            ->where('photos', '!=', 'null')
            ->get();

        foreach ($userProfiles as $profile) {
            $photos = json_decode($profile->photos, true);

            if (!is_array($photos) || empty($photos)) {
                continue;
            }

            foreach ($photos as $photo) {
                // Skip if photo data is invalid
                if (!isset($photo['url']) && !isset($photo['photo_url'])) {
                    continue;
                }

                // Check if this photo already exists in user_photos table
                $photoUrl = $photo['url'] ?? $photo['photo_url'] ?? null;

                if (!$photoUrl) {
                    continue;
                }

                $exists = DB::table('user_photos')
                    ->where('user_id', $profile->user_id)
                    ->where('photo_url', $photoUrl)
                    ->exists();

                if ($exists) {
                    continue; // Skip duplicates
                }

                // Insert into user_photos table
                DB::table('user_photos')->insert([
                    'user_id' => $profile->user_id,
                    'photo_url' => $photoUrl,
                    'order' => $photo['order'] ?? 0,
                    'is_primary' => $photo['is_primary'] ?? false,
                    'is_verified' => false,
                    'created_at' => $photo['uploaded_at'] ?? now(),
                    'updated_at' => now(),
                ]);
            }
        }

        echo "Migrated photos from user_profiles to user_photos table successfully.\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration is not easily reversible since we don't want to lose data
        // If you need to rollback, manually handle the data
        echo "Rolling back this migration will not delete user_photos records to prevent data loss.\n";
    }
};
