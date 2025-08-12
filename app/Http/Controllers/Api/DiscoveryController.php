<?php

namespace App\Http\Controllers\Api;

//use App\Http\Controllers\Controller;
use App\Models\UserProfile;
use App\Models\Like;
use App\Models\ProfileView;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class DiscoveryController extends Controller
{
    public function discover(Request $request): JsonResponse
    {
        // In real app, get user from token
        $user = UserProfile::where('username', 'demo')->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        // Get users that haven't been liked/passed by current user
        $likedUserIds = Like::where('liker_id', $user->user_id)->pluck('liked_id')->toArray();

        $query = UserProfile::where('user_id', '!=', $user->user_id)
            ->where('is_active', true)
            ->whereNotIn('user_id', $likedUserIds);

        // Apply age filter
        $query->whereBetween(DB::raw('YEAR(CURDATE()) - YEAR(date_of_birth)'), [
            $user->preferred_age_range[0] ?? 18,
            $user->preferred_age_range[1] ?? 65
        ]);

        // Apply gender filter
        if ($user->preferred_genders) {
            $query->whereIn('gender', $user->preferred_genders);
        }

        // Apply distance filter if coordinates are available
        if ($user->latitude && $user->longitude) {
            $query->selectRaw("
                user_profiles.*,
                (3959 * acos(cos(radians(?)) * cos(radians(latitude)) *
                cos(radians(longitude) - radians(?)) + sin(radians(?)) *
                sin(radians(latitude)))) AS distance
            ", [$user->latitude, $user->longitude, $user->latitude])
            ->having('distance', '<=', $user->preferred_distance);
        }

        // Apply additional filters from request
        if ($request->has('education_level') && $request->education_level) {
            $query->where('education_level', $request->education_level);
        }

        if ($request->has('occupation') && $request->occupation) {
            $query->where('occupation', 'like', '%' . $request->occupation . '%');
        }

        if ($request->has('min_height') && $request->has('max_height')) {
            $query->whereBetween('height', [$request->min_height, $request->max_height]);
        }

        // Order by last active and limit results
        $profiles = $query->orderBy('last_active_at', 'desc')
            ->limit(20)
            ->get();

        // Add calculated fields
        $profiles->each(function ($profile) use ($user) {
            $profile->age = $profile->age;
            $profile->distance_miles = $user->distanceFrom($profile);
            $profile->is_online = $profile->isOnline();
            $profile->images = collect($profile->photos)->pluck('url')->toArray();
            $profile->verified = $profile->is_verified;
            $profile->lastActive = $profile->last_active_at ? $profile->last_active_at->diffForHumans() : 'Recently';
        });

        return response()->json([
            'success' => true,
            'data' => $profiles
        ]);
    }

    public function like(Request $request, $targetUserId): JsonResponse
    {
        // In real app, get user from token
        $user = UserProfile::where('username', 'demo')->first();
        $targetUser = UserProfile::where('user_id', $targetUserId)->first();

        if (!$user || !$targetUser) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        if ($user->user_id === $targetUser->user_id) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot like yourself'
            ], 400);
        }

        // Check if already liked
        $existingLike = Like::where('liker_id', $user->user_id)
            ->where('liked_id', $targetUser->user_id)
            ->first();

        if ($existingLike) {
            return response()->json([
                'success' => false,
                'message' => 'Already liked this user'
            ], 400);
        }

        $isSuperLike = $request->boolean('is_super_like', false);

        // Check super like limits for non-premium users
        if ($isSuperLike && !$user->isPremium()) {
            $todaySuperLikes = Like::where('liker_id', $user->user_id)
                ->where('is_super_like', true)
                ->whereDate('created_at', today())
                ->count();

            if ($todaySuperLikes >= 5) {
                return response()->json([
                    'success' => false,
                    'message' => 'Daily super like limit reached'
                ], 400);
            }
        }

        // Create the like
        $like = Like::create([
            'liker_id' => $user->user_id,
            'liked_id' => $targetUser->user_id,
            'is_super_like' => $isSuperLike,
            'expires_at' => now()->addDays(30),
        ]);

        // Check if it's a match (mutual like)
        $mutualLike = Like::where('liker_id', $targetUser->user_id)
            ->where('liked_id', $user->user_id)
            ->first();

        $isMatch = false;
        if ($mutualLike) {
            // Create match
            DB::table('matches')->insert([
                'user1_id' => min($user->user_id, $targetUser->user_id),
                'user2_id' => max($user->user_id, $targetUser->user_id),
                'matched_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Update like statuses
            $like->update(['status' => 'matched']);
            $mutualLike->update(['status' => 'matched']);

            $isMatch = true;
        }

        // Record profile view
        ProfileView::create([
            'viewer_id' => $user->user_id,
            'viewed_id' => $targetUser->user_id,
            'viewed_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => $isMatch ? 'It\'s a match!' : 'Like sent successfully',
            'data' => [
                'is_match' => $isMatch,
                'is_super_like' => $isSuperLike,
            ]
        ]);
    }

    public function pass(Request $request, $targetUserId): JsonResponse
    {
        // In real app, get user from token
        $user = UserProfile::where('username', 'demo')->first();
        $targetUser = UserProfile::where('user_id', $targetUserId)->first();

        if (!$user || !$targetUser) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        // Record profile view
        ProfileView::create([
            'viewer_id' => $user->user_id,
            'viewed_id' => $targetUser->user_id,
            'viewed_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Profile passed'
        ]);
    }
}
