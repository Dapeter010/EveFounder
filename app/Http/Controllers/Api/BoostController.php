<?php

namespace App\Http\Controllers\Api;

//use App\Http\Controllers\Controller;
use App\Models\UserProfile;
use App\Models\ProfileBoost;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;

class BoostController extends Controller
{
    public function index(): JsonResponse
    {
        $boostOptions = [
            [
                'id' => 'profile',
                'name' => 'Profile Boost',
                'icon' => 'TrendingUp',
                'price' => '£4.99',
                'duration' => '30 minutes',
                'description' => 'Be one of the top profiles in your area for 30 minutes',
                'benefits' => [
                    'Up to 10x more profile views',
                    'Priority placement in discovery',
                    'Increased match potential',
                    'Real-time boost analytics'
                ],
                'color' => 'from-purple-500 to-indigo-600'
            ],
            [
                'id' => 'super',
                'name' => 'Super Boost',
                'icon' => 'Zap',
                'price' => '£9.99',
                'duration' => '3 hours',
                'description' => 'Maximum visibility for 3 hours with premium features',
                'benefits' => [
                    'Up to 25x more profile views',
                    'Top position in all discovery feeds',
                    'Free Super Likes included',
                    'Priority customer support',
                    'Detailed analytics report'
                ],
                'color' => 'from-yellow-500 to-orange-600',
                'popular' => true
            ],
            [
                'id' => 'weekend',
                'name' => 'Weekend Boost',
                'icon' => 'Star',
                'price' => '£14.99',
                'duration' => 'Full weekend',
                'description' => 'Premium visibility throughout the entire weekend',
                'benefits' => [
                    'Featured profile all weekend',
                    'Unlimited Super Likes',
                    'Priority in all matches',
                    'Weekend activity report',
                    'Money-back guarantee'
                ],
                'color' => 'from-pink-500 to-purple-600'
            ]
        ];

        return response()->json([
            'success' => true,
            'data' => $boostOptions
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'boost_type' => 'required|in:profile,super,weekend',
            'cost' => 'required|numeric|min:0',
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

        // Calculate boost duration based on type
        $duration = match ($request->boost_type) {
            'profile' => 30, // 30 minutes
            'super' => 180, // 3 hours
            'weekend' => 2880, // 48 hours (full weekend)
            default => 30,
        };

        $boost = ProfileBoost::create([
            'user_id' => $user->user_id,
            'boost_type' => $request->boost_type,
            'cost' => $request->cost,
            'starts_at' => now(),
            'ends_at' => now()->addMinutes($duration),
            'status' => 'active',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Boost purchased successfully',
            'data' => $boost
        ]);
    }

    public function history(Request $request): JsonResponse
    {
        // In real app, get user from token
        $user = UserProfile::where('username', 'demo')->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        $boosts = ProfileBoost::where('user_id', $user->user_id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($boost) {
                return [
                    'date' => $boost->created_at->format('Y-m-d'),
                    'type' => ucfirst($boost->boost_type) . ' Boost',
                    'views' => $boost->views_gained,
                    'likes' => $boost->likes_gained,
                    'matches' => $boost->matches_gained,
                    'cost' => '£' . number_format($boost->cost, 2),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $boosts
        ]);
    }
}
