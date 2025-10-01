<?php

namespace App\Http\Controllers\Api;

//use App\Http\Controllers\Controller;
use App\Models\Like;
use App\Models\User;
use App\Events\NewMatchNotification;
use App\Events\NewLikeNotification;
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

    $userProfile = $user->userProfile;

    if (!$userProfile) {
        return response()->json([
            'success' => false,
            'message' => 'User profile not found'
        ], 400);
    }

    // Get users already liked or passed
    $excludedUserIds = Like::where('liker_id', $user->id)
        ->pluck('liked_id')
        ->toArray();
    $excludedUserIds[] = $user->id;

    $query = User::with(['photos', 'userProfile'])
        ->whereNotIn('id', $excludedUserIds)
        ->where('is_active', true)
        ->where('is_verified', true);

    // Age filter
    $minAge = $request->has('min_age') ? $request->min_age : $userProfile->preferred_age_range['min'] ?? null;
    $maxAge = $request->has('max_age') ? $request->max_age : $userProfile->preferred_age_range['max'] ?? null;

    if ($minAge && $maxAge) {
        $minDate = Carbon::now()->subYears($maxAge)->format('Y-m-d');
        $maxDate = Carbon::now()->subYears($minAge)->format('Y-m-d');
        $query->whereBetween('date_of_birth', [$minDate, $maxDate]);
    }

    // Gender preferences
    $interestedGenders = $userProfile->preferred_genders;
    if ($interestedGenders) {
        $interestedGenders = is_string($interestedGenders) ? json_decode($interestedGenders, true) : $interestedGenders;
        if (!empty($interestedGenders)) {
            $query->whereIn('gender', $interestedGenders);
        }
    }

    // Distance filter
    $maxDistance = $request->has('max_distance') ? $request->max_distance : $userProfile->preferred_distance ?? null;
    if ($user->latitude && $user->longitude && $maxDistance) {
        $query->selectRaw("
            users.*,
            (6371 * acos(cos(radians(?)) * cos(radians(latitude)) *
            cos(radians(longitude) - radians(?)) + sin(radians(?)) *
            sin(radians(latitude)))) AS distance
        ", [$user->latitude, $user->longitude, $user->latitude])
        ->having('distance', '<=', $maxDistance);
    }

    // Advanced Filters - Premium Only
    // Check if user has Premium subscription for advanced filters
    $subscription = $user->subscription;
    $isPremium = $subscription && $subscription->isPremium();

    // These are ADVANCED filters - only available to Premium users
    if ($request->has('education') && $request->education && $request->education !== 'any') {
        if (!$isPremium) {
            return response()->json([
                'success' => false,
                'message' => 'Education filter requires Premium subscription',
                'requires_premium' => true
            ]);
        }
        $query->whereHas('userProfile', function ($q) use ($request) {
            $q->where('education_level', $request->education);
        });
    }

    if ($request->has('profession') && $request->profession && $request->profession !== 'any') {
        if (!$isPremium) {
            return response()->json([
                'success' => false,
                'message' => 'Profession filter requires Premium subscription',
                'requires_premium' => true
            ]);
        }
        $query->whereHas('userProfile', function ($q) use ($request) {
            $q->where('occupation', $request->profession);
        });
    }

    if ($request->has('min_height') && $request->min_height) {
        if (!$isPremium) {
            return response()->json([
                'success' => false,
                'message' => 'Height filter requires Premium subscription',
                'requires_premium' => true
            ]);
        }
        $query->whereHas('userProfile', function ($q) use ($request) {
            $q->where('height', '>=', $request->min_height);
        });
    }

    if ($request->has('max_height') && $request->max_height) {
        if (!$isPremium) {
            return response()->json([
                'success' => false,
                'message' => 'Height filter requires Premium subscription',
                'requires_premium' => true
            ]);
        }
        $query->whereHas('userProfile', function ($q) use ($request) {
            $q->where('height', '<=', $request->max_height);
        });
    }

    if ($request->has('relationship_type') && $request->relationship_type && $request->relationship_type !== 'any') {
        if (!$isPremium) {
            return response()->json([
                'success' => false,
                'message' => 'Relationship type filter requires Premium subscription',
                'requires_premium' => true
            ]);
        }
        $query->whereHas('userProfile', function ($q) use ($request) {
            $q->where('relationship_goals', $request->relationship_type);
        });
    }

    // Pagination
    $page = $request->get('page', 1);
    $perPage = 20;
    $offset = ($page - 1) * $perPage;

    $totalCount = $query->count();

    // Boost visibility - boosted profiles appear first
    // Get users with active boosts
    $boostedUserIds = \App\Models\ProfileBoost::where('status', 'active')
        ->where('ends_at', '>', now())
        ->pluck('user_id')
        ->toArray();

    // Order by boost status first, then by last active
    $users = $query->orderByRaw(
        'CASE WHEN id IN (' . implode(',', array_merge($boostedUserIds, [0])) . ') THEN 0 ELSE 1 END'
    )
        ->orderBy('last_active_at', 'desc')
        ->offset($offset)
        ->limit($perPage)
        ->get();

    $profiles = $users->map(function ($user) {
        $profile = $user->userProfile;
        $photos = $user->photos->sortBy('order')->pluck('photo_url')->toArray();

        $age = Carbon::parse($user->date_of_birth)->age;

        $distance = isset($user->distance) ? round($user->distance, 1) . ' miles away' : $profile->location;

        $heightFormatted = null;
        if ($profile->height) {
            $feet = floor($profile->height / 30.48);
            $inches = round(($profile->height / 30.48 - $feet) * 12);
            $heightFormatted = $feet . "'" . $inches . '"';
        }

        $lastActive = 'Active recently';
        if ($user->last_active_at) {
            $lastActiveTime = Carbon::parse($user->last_active_at);
            if ($lastActiveTime->isToday()) $lastActive = 'Active today';
            elseif ($lastActiveTime->isYesterday()) $lastActive = 'Active yesterday';
            else $lastActive = 'Active ' . $lastActiveTime->diffForHumans();
        }

        $interests = is_string($profile->interests) ? json_decode($profile->interests, true) ?? [] : $profile->interests ?? [];

        return [
            'id' => $user->id,
            'name' => $profile->first_name,
            'age' => $age,
            'location' => $profile->location,
            'distance' => $distance,
            'profession' => $profile->occupation,
            'education' => $profile->education_level,
            'height' => $heightFormatted,
            'images' => $photos,
            'photos' => $photos,
            'bio' => $profile->bio,
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
        $subscription = $user->subscription;

        // Check subscription and enforce limits
        $isPremium = $subscription && $subscription->isPremium();
        $isBasic = $subscription && $subscription->isBasic();
        $isFree = !$isPremium && !$isBasic;

        // Daily like limit check for FREE users
        if ($isFree && !$isSuperLike) {
            $todayLikes = Like::where('liker_id', $user->id)
                ->where('is_super_like', false)
                ->whereDate('created_at', today())
                ->count();

            if ($todayLikes >= 10) {
                return response()->json([
                    'success' => false,
                    'message' => 'Daily like limit reached (10/day). Upgrade to Premium for unlimited likes.',
                    'requires_upgrade' => true,
                    'limit_reached' => true
                ]);
            }
        }

        // Super Like limit check
        if ($isSuperLike) {
            $todaySuperLikes = Like::where('liker_id', $user->id)
                ->where('is_super_like', true)
                ->whereDate('created_at', today())
                ->count();

            if ($isFree && $todaySuperLikes >= 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Daily super like limit reached (1/day). Upgrade to Basic for 5/day or Premium for unlimited.',
                    'requires_upgrade' => true,
                    'limit_reached' => true
                ]);
            }

            if ($isBasic && $todaySuperLikes >= 5) {
                return response()->json([
                    'success' => false,
                    'message' => 'Daily super like limit reached (5/day). Upgrade to Premium for unlimited super likes.',
                    'requires_upgrade' => true,
                    'limit_reached' => true
                ]);
            }

            // Premium users have unlimited super likes
        }

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

            // Broadcast match notifications to both users
            try {
                $targetUser = User::find($targetUserId);
                if ($targetUser) {
                    // Notify current user
                    broadcast(new NewMatchNotification($user, $targetUser));
                    // Notify target user
                    broadcast(new NewMatchNotification($targetUser, $user));
                }
            } catch (\Exception $e) {
                Log::error('Failed to broadcast match notification: ' . $e->getMessage());
            }
        }

        // Broadcast notification to target user about new like (even if not a match yet)
        if (!$isMatch) {
            try {
                $targetUser = User::find($targetUserId);
                if ($targetUser) {
                    broadcast(new NewLikeNotification($targetUser));
                }
            } catch (\Exception $e) {
                Log::error('Failed to broadcast like notification: ' . $e->getMessage());
            }
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
