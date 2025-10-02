<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;

class MobileNotificationController extends Controller
{
    /**
     * Update user's FCM token
     */
    public function updateFcmToken(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'fcm_token' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => implode(", ", $validator->errors()->all()),
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();
        $user->update(['fcm_token' => $request->fcm_token]);

        return response()->json([
            'success' => true,
            'message' => 'FCM token updated successfully'
        ]);
    }

    /**
     * Remove FCM token (on logout)
     */
    public function removeFcmToken(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->update(['fcm_token' => null]);

        return response()->json([
            'success' => true,
            'message' => 'FCM token removed successfully'
        ]);
    }

    /**
     * Update notification settings
     */
    public function updateSettings(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'new_matches' => 'required|boolean',
            'messages' => 'required|boolean',
            'likes' => 'required|boolean',
            'marketing' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => implode(", ", $validator->errors()->all()),
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();

        // Store notification settings as JSON
        $user->notification_settings = [
            'new_matches' => $request->boolean('new_matches'),
            'messages' => $request->boolean('messages'),
            'likes' => $request->boolean('likes'),
            'marketing' => $request->boolean('marketing'),
        ];

        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Notification settings updated successfully',
            'data' => $user->notification_settings
        ]);
    }

    /**
     * Get notification settings
     */
    public function getSettings(Request $request): JsonResponse
    {
        $user = $request->user();

        // Default settings if not set
        $settings = $user->notification_settings ?? [
            'new_matches' => true,
            'messages' => true,
            'likes' => true,
            'marketing' => false,
        ];

        return response()->json([
            'success' => true,
            'data' => $settings
        ]);
    }
}
