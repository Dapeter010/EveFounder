<?php

namespace App\Http\Controllers\Api;

//use App\Http\Controllers\Controller;
use App\Models\UserProfile;
use App\Models\Report;
use App\Models\BlockedUser;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;

class ReportController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'reported_id' => 'required|string|exists:user_profiles,user_id',
            'type' => 'required|in:inappropriate_behavior,fake_profile,harassment,spam,other',
            'reason' => 'required|string|max:500',
            'description' => 'nullable|string|max:1000',
            'evidence' => 'nullable|array',
            'evidence.*' => 'string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => implode(",", $validator->errors()->all()),
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

        if ($user->id === $request->reported_id) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot report yourself'
            ], 400);
        }

        $report = Report::create([
            'reporter_id' => $user->id,
            'reported_id' => $request->reported_id,
            'type' => $request->type,
            'reason' => $request->reason,
            'description' => $request->description,
            'evidence' => $request->evidence,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Report submitted successfully',
            'data' => $report
        ]);
    }

    public function blockUser(Request $request, $userId): JsonResponse
    {
        // In real app, get user from token
        $user = UserProfile::where('username', 'demo')->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        $targetUser = UserProfile::where('user_id', $userId)->first();

        if (!$targetUser) {
            return response()->json([
                'success' => false,
                'message' => 'Target user not found'
            ], 404);
        }

        if ($user->id === $userId) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot block yourself'
            ], 400);
        }

        // Check if already blocked
        $existingBlock = BlockedUser::where('blocker_id', $user->id)
            ->where('blocked_id', $userId)
            ->first();

        if ($existingBlock) {
            return response()->json([
                'success' => false,
                'message' => 'User already blocked'
            ], 400);
        }

        BlockedUser::create([
            'blocker_id' => $user->id,
            'blocked_id' => $userId,
            'reason' => $request->reason,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'User blocked successfully'
        ]);
    }

    public function getBlockedUsers(Request $request): JsonResponse
    {
        // In real app, get user from token
        $user = UserProfile::where('username', 'demo')->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        $blockedUsers = BlockedUser::where('blocker_id', $user->id)
            ->with('blocked')
            ->get()
            ->map(function ($block) {
                return $block->blocked;
            });

        return response()->json([
            'success' => true,
            'data' => $blockedUsers
        ]);
    }
}
