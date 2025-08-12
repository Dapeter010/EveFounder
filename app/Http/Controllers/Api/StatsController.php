<?php

namespace App\Http\Controllers\Api;

//use App\Http\Controllers\Controller;
use App\Models\UserProfile;
use App\Models\Like;
use App\Models\ProfileView;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class StatsController extends Controller
{
    public function getUserStats(Request $request): JsonResponse
    {
        // In real app, get user from token
        $user = UserProfile::where('username', 'demo')->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        $stats = [
            'profile_views_today' => ProfileView::where('viewed_id', $user->user_id)
                ->whereDate('viewed_at', today())
                ->count(),
            'likes_received_new' => Like::where('liked_id', $user->user_id)
                ->where('status', 'pending')
                ->count(),
            'total_matches' => DB::table('matches')
                ->where(function ($query) use ($user) {
                    $query->where('user1_id', $user->user_id)
                          ->orWhere('user2_id', $user->user_id);
                })
                ->where('is_active', true)
                ->count(),
            'unread_messages' => Message::where('receiver_id', $user->user_id)
                ->whereNull('read_at')
                ->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    public function updateLocation(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // In real app, get user from token
        $user = UserProfile::where('username', 'demo')->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        $user->update([
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Location updated successfully'
        ]);
    }

    public function getFilterOptions(): JsonResponse
    {
        $options = [
            'educationLevels' => [
                'high-school' => 'High School',
                'some-college' => 'Some College',
                'bachelors' => 'Bachelor\'s Degree',
                'masters' => 'Master\'s Degree',
                'phd' => 'PhD',
                'trade-school' => 'Trade School',
                'other' => 'Other'
            ],
            'professions' => [
                'Healthcare', 'Technology', 'Education', 'Finance', 'Marketing',
                'Engineering', 'Legal', 'Creative Arts', 'Business', 'Science', 'Other'
            ],
            'relationshipTypes' => [
                'casual' => 'Something casual',
                'long-term' => 'Long-term relationship',
                'marriage' => 'Marriage',
                'friendship' => 'New friends',
                'other' => 'Not sure yet'
            ],
            'interests' => [
                'Travel', 'Fitness', 'Music', 'Cooking', 'Reading', 'Movies',
                'Photography', 'Dancing', 'Art', 'Sports', 'Gaming', 'Fashion',
                'Technology', 'Nature', 'Food', 'Comedy', 'Wine', 'Coffee',
                'Hiking', 'Swimming', 'Yoga', 'Meditation', 'Volunteering',
                'Pets', 'Cars', 'Motorcycles', 'Cycling', 'Running', 'Climbing'
            ],
            'ethnicities' => [
                'White British', 'White Irish', 'White Other', 'Mixed White and Black Caribbean',
                'Mixed White and Black African', 'Mixed White and Asian', 'Mixed Other',
                'Asian British', 'Indian', 'Pakistani', 'Bangladeshi', 'Chinese',
                'Asian Other', 'Black British', 'Black Caribbean', 'Black African',
                'Black Other', 'Arab', 'Other'
            ],
            'hairColors' => ['black', 'brown', 'blonde', 'red', 'gray', 'white', 'other'],
            'eyeColors' => ['brown', 'blue', 'green', 'hazel', 'gray', 'other'],
            'bodyTypes' => ['slim', 'athletic', 'average', 'curvy', 'plus-size', 'muscular'],
            'drinkingHabits' => ['never', 'rarely', 'socially', 'regularly', 'prefer-not-to-say'],
            'smokingHabits' => ['never', 'rarely', 'socially', 'regularly', 'trying-to-quit', 'prefer-not-to-say'],
            'exerciseFrequencies' => ['never', 'rarely', 'sometimes', 'regularly', 'daily'],
            'religions' => ['christian', 'muslim', 'jewish', 'hindu', 'buddhist', 'atheist', 'agnostic', 'spiritual', 'other']
        ];

        return response()->json([
            'success' => true,
            'data' => $options
        ]);
    }
}
