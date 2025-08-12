<?php

namespace App\Http\Controllers\Api;

//use App\Http\Controllers\Controller;
use App\Models\UserProfile;
use App\Models\Like;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class MatchController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        // In real app, get user from token
        $user = UserProfile::where('username', 'demo')->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        $matches = DB::table('matches')
            ->where(function ($query) use ($user) {
                $query->where('user1_id', $user->user_id)
                      ->orWhere('user2_id', $user->user_id);
            })
            ->where('is_active', true)
            ->get()
            ->map(function ($match) use ($user) {
                $otherUserId = $match->user1_id === $user->user_id ? $match->user2_id : $match->user1_id;
                $otherUser = UserProfile::where('user_id', $otherUserId)->first();

                return [
                    'id' => $match->id,
                    'user' => $otherUser,
                    'matched_at' => $match->matched_at,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $matches
        ]);
    }

    public function getReceivedLikes(Request $request): JsonResponse
    {
        // In real app, get user from token
        $user = UserProfile::where('username', 'demo')->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        $likes = Like::where('liked_id', $user->user_id)
            ->where('status', 'pending')
            ->with(['liker' => function ($query) {
                $query->select('user_id', 'first_name', 'last_name', 'photos');
            }])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $likes
        ]);
    }

    public function getSentLikes(Request $request): JsonResponse
    {
        // In real app, get user from token
        $user = UserProfile::where('username', 'demo')->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        $likes = Like::where('liker_id', $user->user_id)
            ->with(['liked' => function ($query) {
                $query->select('user_id', 'first_name', 'last_name', 'photos');
            }])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $likes
        ]);
    }
}
