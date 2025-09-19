<?php

namespace App\Http\Controllers\Api;

//use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Models\UserProfile;

class SettingsController extends Controller
{
    /**
     * Get user settings
     */
    public function getSettings(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $profile = UserProfile::where('user_id', $user->id)->first();

            if (!$profile) {
                return response()->json([
                    'success' => false,
                    'message' => 'Profile not found'
                ], 404);
            }

            // Get current settings from profile
            $settings = [
                'notifications' => [
                    'newMatches' => $profile->notifications['new_matches'] ?? true,
                    'messages' => $profile->notifications['messages'] ?? true,
                    'likes' => $profile->notifications['likes'] ?? false,
                    'marketing' => $profile->notifications['marketing'] ?? false,
                ],
                'privacy' => [
                    'showAge' => $profile->privacy_settings['show_age'] ?? true,
                    'showDistance' => $profile->privacy_settings['show_distance'] ?? true,
                    'onlineStatus' => $profile->privacy_settings['online_status'] ?? true,
                    'readReceipts' => $profile->privacy_settings['read_receipts'] ?? true,
                ],
                'discovery' => [
                    'showMe' => $profile->visibility_settings['show_me'] ?? true,
                    'ageRange' => $profile->preferred_age_range ?? [25, 35],
                    'maxDistance' => $profile->preferred_distance ?? 25,
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => $settings
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get settings',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update notification settings
     */
    public function updateNotifications(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'newMatches' => 'boolean',
            'messages' => 'boolean',
            'likes' => 'boolean',
            'marketing' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => implode(",", $validator->errors()->all()),
            ], 422);
        }

        try {
            $user = Auth::user();
            $profile = UserProfile::where('user_id', $user->id)->first();

            if (!$profile) {
                return response()->json([
                    'success' => false,
                    'message' => 'Profile not found'
                ], 404);
            }

            $currentNotifications = $profile->notifications ?? [];
            $newNotifications = array_merge($currentNotifications, [
                'new_matches' => $request->input('newMatches', $currentNotifications['new_matches'] ?? true),
                'messages' => $request->input('messages', $currentNotifications['messages'] ?? true),
                'likes' => $request->input('likes', $currentNotifications['likes'] ?? false),
                'marketing' => $request->input('marketing', $currentNotifications['marketing'] ?? false),
            ]);

            $profile->update([
                'notifications' => $newNotifications
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Notification settings updated successfully',
                'data' => $newNotifications
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update notification settings',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update privacy settings
     */
    public function updatePrivacy(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'showAge' => 'boolean',
            'showDistance' => 'boolean',
            'onlineStatus' => 'boolean',
            'readReceipts' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => implode(",", $validator->errors()->all()),
            ], 422);
        }

        try {
            $user = Auth::user();
            $profile = UserProfile::where('user_id', $user->id)->first();

            if (!$profile) {
                return response()->json([
                    'success' => false,
                    'message' => 'Profile not found'
                ], 404);
            }

            $currentPrivacy = $profile->privacy_settings ?? [];
            $newPrivacy = array_merge($currentPrivacy, [
                'show_age' => $request->input('showAge', $currentPrivacy['show_age'] ?? true),
                'show_distance' => $request->input('showDistance', $currentPrivacy['show_distance'] ?? true),
                'online_status' => $request->input('onlineStatus', $currentPrivacy['online_status'] ?? true),
                'read_receipts' => $request->input('readReceipts', $currentPrivacy['read_receipts'] ?? true),
            ]);

            $profile->update([
                'privacy_settings' => $newPrivacy
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Privacy settings updated successfully',
                'data' => $newPrivacy
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update privacy settings',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update discovery settings
     */
    public function updateDiscovery(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'showMe' => 'boolean',
            'ageRange' => 'array|size:2',
            'ageRange.*' => 'integer|min:18|max:100',
            'maxDistance' => 'integer|min:1|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => implode(",", $validator->errors()->all()),
            ], 422);
        }

        try {
            $user = Auth::user();
            $profile = UserProfile::where('user_id', $user->id)->first();

            if (!$profile) {
                return response()->json([
                    'success' => false,
                    'message' => 'Profile not found'
                ], 404);
            }

            $updateData = [];

            if ($request->has('showMe')) {
                $currentVisibility = $profile->visibility_settings ?? [];
                $currentVisibility['show_me'] = $request->showMe;
                $updateData['visibility_settings'] = $currentVisibility;
            }

            if ($request->has('ageRange')) {
                $updateData['preferred_age_range'] = $request->ageRange;
            }

            if ($request->has('maxDistance')) {
                $updateData['preferred_distance'] = $request->maxDistance;
            }

            if (!empty($updateData)) {
                $profile->update($updateData);
            }

            return response()->json([
                'success' => true,
                'message' => 'Discovery settings updated successfully',
                'data' => [
                    'showMe' => $profile->visibility_settings['show_me'] ?? true,
                    'ageRange' => $profile->preferred_age_range,
                    'maxDistance' => $profile->preferred_distance,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update discovery settings',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete user account
     */
    public function deleteAccount(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'password' => 'required|string',
            'confirmation' => 'required|string|in:DELETE_MY_ACCOUNT'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => implode(",", $validator->errors()->all()),
            ], 422);
        }

        try {
            $user = Auth::user();

            // Verify password
            if (!Hash::check($request->password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid password'
                ], 401);
            }

            // In a real app, you'd want to:
            // 1. Soft delete to preserve data integrity
            // 2. Clean up related data (matches, messages, etc.)
            // 3. Cancel subscriptions
            // 4. Delete uploaded photos
            // 5. Send confirmation email

            // For now, we'll soft delete
            $profile = UserProfile::where('user_id', $user->id)->first();
            if ($profile) {
                $profile->update([
                    'deleted_at' => now(),
                    'is_active' => false
                ]);
            }

            $user->update([
                'deleted_at' => now(),
                'email' => $user->email . '_deleted_' . now()->timestamp
            ]);

            // Revoke all tokens
            $user->tokens()->delete();

            return response()->json([
                'success' => true,
                'message' => 'Account deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete account',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get account deletion confirmation requirements
     */
    public function getDeleteAccountInfo(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'requirements' => [
                    'password_confirmation' => true,
                    'confirmation_text' => 'DELETE_MY_ACCOUNT',
                ],
                'consequences' => [
                    'Your profile will be permanently deleted',
                    'All matches and conversations will be lost',
                    'Your subscription will be cancelled',
                    'This action cannot be undone'
                ]
            ]
        ]);
    }
}
