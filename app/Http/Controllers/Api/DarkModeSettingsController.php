<?php

namespace App\Http\Controllers\Api;

use App\Models\DarkModeSettings;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;

class DarkModeSettingsController extends Controller
{
    /**
     * Get user's dark mode settings
     */
    public function getSettings(Request $request): JsonResponse
    {
        $user = $request->user();

        // Check if user has dark mode enabled
        if (!$user->dark_mode_enabled) {
            return response()->json([
                'success' => false,
                'message' => 'Dark mode features are not enabled for this account'
            ], 403);
        }

        $settings = DarkModeSettings::getOrCreateForUser($user->id);

        return response()->json([
            'success' => true,
            'data' => $settings
        ]);
    }

    /**
     * Update user's dark mode settings
     */
    public function updateSettings(Request $request): JsonResponse
    {
        $user = $request->user();

        // Check if user has dark mode enabled
        if (!$user->dark_mode_enabled) {
            return response()->json([
                'success' => false,
                'message' => 'Dark mode features are not enabled for this account'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'invisible_mode' => 'sometimes|boolean',
            'ghost_mode' => 'sometimes|boolean',
            'location_obfuscation_enabled' => 'sometimes|boolean',
            'location_obfuscation_radius' => 'sometimes|integer|min:1|max:100',
            'screenshot_prevention' => 'sometimes|boolean',
            'auto_delete_messages' => 'sometimes|boolean',
            'auto_delete_delay' => 'sometimes|integer|min:5|max:300',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => implode(", ", $validator->errors()->all()),
            ], 422);
        }

        $settings = DarkModeSettings::getOrCreateForUser($user->id);

        // Update only the fields that are present in the request
        $settings->update($request->only([
            'invisible_mode',
            'ghost_mode',
            'location_obfuscation_enabled',
            'location_obfuscation_radius',
            'screenshot_prevention',
            'auto_delete_messages',
            'auto_delete_delay',
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Dark mode settings updated successfully',
            'data' => $settings->fresh()
        ]);
    }

    /**
     * Reset all dark mode settings to default
     */
    public function resetSettings(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->dark_mode_enabled) {
            return response()->json([
                'success' => false,
                'message' => 'Dark mode features are not enabled for this account'
            ], 403);
        }

        $settings = DarkModeSettings::getOrCreateForUser($user->id);

        $settings->update([
            'invisible_mode' => false,
            'ghost_mode' => false,
            'location_obfuscation_enabled' => false,
            'location_obfuscation_radius' => 5,
            'screenshot_prevention' => false,
            'auto_delete_messages' => false,
            'auto_delete_delay' => 30,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Dark mode settings reset to default',
            'data' => $settings->fresh()
        ]);
    }
}
