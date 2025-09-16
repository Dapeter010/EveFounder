<?php

namespace App\Http\Controllers\Api;

//use App\Http\Controllers\Controller;
use App\Models\Like;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DiscoveryController extends Controller
{
    /**
     * Get potential matches based on user preferences and location
     */
    public function discover(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required'
            ], 401);
        }

        // Get user's preferences
        $preferences = $user->preferences;

        if (!$preferences) {
            return response()->json([
                'success' => false,
                'message' => 'Please set your preferences first'
            ], 400);
        }

        // Get users that current user has already liked or passed
        $excludedUserIds = Like::where('liker_id', $user->id)
            ->pluck('liked_id')
            ->toArray();
        Log::info("We excluded " . sizeof($excludedUserIds));

        // Add current user to excluded list
        $excludedUserIds[] = $user->id;

        // Build discovery query
        $query = User::with(['photos', 'userProfile'])
            ->whereNotIn('id', $excludedUserIds)
            ->where('is_active', true)
            ->where('is_verified', true); // Only show verified users

        Log::info($preferences);

        // Apply age filter - prioritize request parameters over user preferences
        $minAge = $request->has('min_age') ? $request->min_age : $preferences->min_age;
        $maxAge = $request->has('max_age') ? $request->max_age : $preferences->max_age;

        if ($minAge && $maxAge) {
            $minDate = Carbon::now()->subYears($maxAge)->format('Y-m-d');
            $maxDate = Carbon::now()->subYears($minAge)->format('Y-m-d');
            $query->whereBetween('date_of_birth', [$minDate, $maxDate]);
            Log::info("Applied age filter: {$minAge} - {$maxAge}");
        }

        // Apply gender preferences
        if ($preferences->interested_genders) {
            $interestedGenders = is_string($preferences->interested_genders)
                ? json_decode($preferences->interested_genders, true)
                : $preferences->interested_genders;

            if (!empty($interestedGenders)) {
                $query->whereIn('gender', $interestedGenders);
            }
        }

        // Apply distance filter - prioritize request parameter over user preference
        $maxDistance = $request->has('max_distance') ? $request->max_distance : $preferences->max_distance;

        if ($user->latitude && $user->longitude && $maxDistance) {
            // Use Haversine formula to calculate distance
            $query->selectRaw("
            users.*,
            (6371 * acos(cos(radians(?)) * cos(radians(latitude)) *
            cos(radians(longitude) - radians(?)) + sin(radians(?)) *
            sin(radians(latitude)))) AS distance
        ", [$user->latitude, $user->longitude, $user->latitude])
                ->having('distance', '<=', $maxDistance);
            Log::info("Applied distance filter: {$maxDistance} miles");
        }

        // Apply education filter (from request)
        if ($request->has('education') && $request->education && $request->education !== 'any') {
            $query->where('education', $request->education);
            Log::info("Applied education filter: " . $request->education);
        }

        // Apply profession filter (from request)
        if ($request->has('profession') && $request->profession && $request->profession !== 'any') {
            $query->where('profession', $request->profession);
            Log::info("Applied profession filter: " . $request->profession);
        }

        // Apply height filters (from request)
        if ($request->has('min_height') && $request->min_height) {
            $query->where('height', '>=', $request->min_height);
            Log::info("Applied min height filter: " . $request->min_height);
        }

        if ($request->has('max_height') && $request->max_height) {
            $query->where('height', '<=', $request->max_height);
            Log::info("Applied max height filter: " . $request->max_height);
        }

        // Apply relationship type filter (from request)
        if ($request->has('relationship_type') && $request->relationship_type && $request->relationship_type !== 'any') {
            $query->where('relationship_type', $request->relationship_type);
            Log::info("Applied relationship type filter: " . $request->relationship_type);
        }

        // Handle pagination
        $page = $request->get('page', 1);
        $perPage = 20;
        $offset = ($page - 1) * $perPage;

        // Get total count for pagination info (optional)
        $totalCount = $query->count();

        // Order by last active and apply pagination
        $users = $query->orderBy('last_active_at', 'desc')
            ->offset($offset)
            ->limit($perPage)
            ->get();

        Log::info("Page {$page}: Got " . sizeof($users) . " users out of {$totalCount} total");

        // Transform data for frontend
        $profiles = $users->map(function ($user) {
            $profile = $user->userProfile;
            $photos = $user->photos->sortBy('order')->pluck('photo_url')->toArray();

            // Calculate age
            $age = Carbon::parse($user->date_of_birth)->age;

            // Calculate distance if coordinates available
            $distance = null;
            if (isset($user->distance)) {
                $distance = round($user->distance, 1) . ' miles away';
            } elseif ($user->location) {
                $distance = $user->location;
            }

            // Format height
            $heightFormatted = null;
            if ($user->height) {
                $feet = floor($user->height / 30.48);
                $inches = round(($user->height / 30.48 - $feet) * 12);
                $heightFormatted = $feet . "'" . $inches . '"';
            }

            // Get last active status
            $lastActive = 'Active recently';
            if ($user->last_active_at) {
                $lastActiveTime = Carbon::parse($user->last_active_at);
                if ($lastActiveTime->isToday()) {
                    $lastActive = 'Active today';
                } elseif ($lastActiveTime->isYesterday()) {
                    $lastActive = 'Active yesterday';
                } else {
                    $lastActive = 'Active ' . $lastActiveTime->diffForHumans();
                }
            }

            // Parse interests safely
            $interests = [];
            if ($user->interests) {
                if (is_string($user->interests)) {
                    $interests = json_decode($user->interests, true) ?? [];
                } else {
                    $interests = $user->interests;
                }
            }

            return [
                'id' => $user->id,
                'name' => $user->first_name,
                'age' => $age,
                'location' => $user->location,
                'distance' => $distance,
                'profession' => $user->profession,
                'education' => $user->education,
                'height' => $heightFormatted,
                'images' => $photos,
                'photos' => $photos, // For compatibility
                'bio' => $user->bio,
                'interests' => $interests,
                'verified' => $user->is_verified,
                'lastActive' => $lastActive,
                'distance_miles' => isset($user->distance) ? round($user->distance, 1) : null,
                'is_online' => $user->last_active_at && Carbon::parse($user->last_active_at)->gt(Carbon::now()->subMinutes(15))
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $profiles,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $totalCount,
                'has_more' => ($offset + $perPage) < $totalCount
            ]
        ]);
    }

    /**
     * Like a user
     */
    public function likeUser(Request $request, $targetUserId): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required'
            ], 401);
        }

        // Validate target user exists
        $targetUser = User::find($targetUserId);
        if (!$targetUser) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        // Check if already liked
        $existingLike = Like::where('liker_id', $user->id)
            ->where('liked_id', $targetUserId)
            ->first();

        if ($existingLike) {
            return response()->json([
                'success' => false,
                'message' => 'Already liked this user'
            ], 400);
        }

        $isSuperLike = $request->input('is_super_like', false);

        // Create like
        $like = Like::create([
            'liker_id' => $user->id,
            'liked_id' => $targetUserId,
            'is_super_like' => $isSuperLike,
            'status' => 'pending',
            'expires_at' => Carbon::now()->addDays(30),
        ]);

        // Check if it's a match (mutual like)
        $mutualLike = Like::where('liker_id', $targetUserId)
            ->where('liked_id', $user->id)
            ->first();

        $isMatch = false;
        if ($mutualLike) {
            // Create match
            DB::table('matches')->insert([
                'user1_id' => min($user->id, $targetUserId),
                'user2_id' => max($user->id, $targetUserId),
                'matched_at' => now(),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Update both likes status
            Like::whereIn('id', [$like->id, $mutualLike->id])
                ->update(['status' => 'matched']);

            $isMatch = true;
        }

        return response()->json([
            'success' => true,
            'message' => $isMatch ? "It's a match!" : 'Like sent successfully',
            'data' => [
                'is_match' => $isMatch,
                'is_super_like' => $isSuperLike
            ]
        ]);
    }

    /**
     * Pass on a user
     */
    public function passUser(Request $request, $targetUserId): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required'
            ], 401);
        }

        // Create a "pass" entry (like with expired status)
        Like::updateOrCreate(
            [
                'liker_id' => $user->id,
                'liked_id' => $targetUserId,
            ],
            [
                'is_super_like' => false,
                'status' => 'expired',
                'expires_at' => now(),
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Profile passed'
        ]);
    }
}
