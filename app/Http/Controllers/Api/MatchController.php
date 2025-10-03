<?php

namespace App\Http\Controllers\Api;

//use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\Like;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MatchController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user(); // Use $request->user() for Sanctum auth

        if (!$user) {
            Log::info("User not logged in");
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated'
            ], 401);
        }

        $matches = DB::table('matches')
            ->where(function ($query) use ($user) {
                $query->where('user1_id', $user->id)
                    ->orWhere('user2_id', $user->id);
            })
            ->where('is_active', true)
            ->orderBy('matched_at', 'desc')
            ->get()
            ->map(function ($match) use ($user) {
                // Get the other user's ID
                $otherUserId = $match->user1_id === $user->id ? $match->user2_id : $match->user1_id;

                // Load the other user with their photos
                $otherUser = User::with(['photos' => function ($query) {
                    $query->orderBy('order', 'asc');
                }])->find($otherUserId);

                if (!$otherUser) {
                    return null; // Skip if user not found
                }

                // Calculate age
                $age = \Carbon\Carbon::parse($otherUser->date_of_birth)->age;

                // Get user photos
                $photos = $otherUser->photos->pluck('photo_url')->toArray();
                $avatar = $photos[0] ?? null;

                // Calculate distance if both users have coordinates
                $distance = null;
                if ($user->latitude && $user->longitude &&
                    $otherUser->latitude && $otherUser->longitude) {

                    $earthRadius = 6371; // Earth's radius in kilometers
                    $latDiff = deg2rad($otherUser->latitude - $user->latitude);
                    $lonDiff = deg2rad($otherUser->longitude - $user->longitude);

                    $a = sin($latDiff / 2) * sin($latDiff / 2) +
                        cos(deg2rad($user->latitude)) * cos(deg2rad($otherUser->latitude)) *
                        sin($lonDiff / 2) * sin($lonDiff / 2);
                    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
                    $distanceKm = $earthRadius * $c;

                    $distance = round($distanceKm * 0.621371, 1) . ' miles away'; // Convert to miles
                } else {
                    $distance = $otherUser->location;
                }

                // Check if there are unread messages from this user
                $hasMessage = DB::table('messages')
                    ->where('match_id', $match->id)
                    ->where('sender_id', $otherUserId)
                    ->where('receiver_id', $user->id)
                    ->whereNull('read_at')
                    ->exists();

                // Check if this match came from a super like
                $superLike = DB::table('likes')
                    ->where(function ($query) use ($user, $otherUserId) {
                        $query->where('liker_id', $user->id)->where('liked_id', $otherUserId);
                    })
                    ->orWhere(function ($query) use ($user, $otherUserId) {
                        $query->where('liker_id', $otherUserId)->where('liked_id', $user->id);
                    })
                    ->where('is_super_like', true)
                    ->exists();

                // Parse interests
                $interests = [];
                if ($otherUser->interests) {
                    $interests = is_string($otherUser->interests)
                        ? json_decode($otherUser->interests, true) ?? []
                        : $otherUser->interests;
                }

                return [
                    'id' => $match->id,
                    'user' => $otherUser,
                    'name' => $otherUser->first_name,
                    'age' => $age,
                    'location' => $otherUser->location,
                    'distance' => $distance,
                    'matchedAt' => $match->matched_at,
                    'avatar' => $avatar,
                    'images' => $photos,
                    'bio' => $otherUser->bio,
                    'interests' => $interests,
                    'hasMessage' => $hasMessage,
                    'superLike' => $superLike
                ];
            })
            ->filter() // Remove null entries
            ->values(); // Re-index array

        return response()->json([
            'success' => true,
            'data' => $matches
        ]);
    }

    public function getReceivedLikes(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        // Check subscription - "See Who Liked You" requires Basic or Premium
        $subscription = $user->subscription;
        $hasAccess = $subscription && ($subscription->isPremium() || $subscription->isBasic());

        if (!$hasAccess) {
            return response()->json([
                'success' => false,
                'message' => 'Upgrade to Basic or Premium to see who liked you',
                'requires_upgrade' => true,
                'feature' => 'see_who_liked_you'
            ]);
        }

        $likes = Like::where('liked_id', $user->id)
            ->where('status', 'pending')
            ->with([
                'liker' => function ($query) {
                    $query->select('user_id', 'first_name', 'last_name', 'date_of_birth', 'location', 'photos');
                }])
            ->orderBy('created_at', 'desc')
            ->get();

        $likes->transform(function ($like) {
            $like->age = \Carbon\Carbon::parse($like->liker->date_of_birth)->age;
            $like->liker->photos = $like->liker->photos->sortBy('order')->pluck('photo_url')->toArray();

            $like->photos = $like->liker->photos;
            return $like;
        });

        return response()->json([
            'success' => true,
            'data' => $likes
        ]);
    }

    public function getSentLikes(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        $likes = Like::where('liker_id', $user->id)
            ->with([
                'liked' => function ($query) {
                    $query->select('user_id', 'first_name', 'last_name', 'date_of_birth', 'location', 'photos');
                }
            ])
            ->orderBy('created_at', 'desc')
            ->get();

        $likes->transform(function ($like) {
            $like->age = \Carbon\Carbon::parse($like->liked->date_of_birth)->age;
            $like->liked->photos = $like->liked->photos->sortBy('order')->pluck('photo_url')->toArray();

            $like->photos = $like->liked->photos;

            return $like;
        });


        return response()->json([
            'success' => true,
            'data' => $likes
        ]);
    }
}
