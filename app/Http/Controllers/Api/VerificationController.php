<?php

namespace App\Http\Controllers\Api;

//use App\Http\Controllers\Controller;
use App\Models\UserProfile;
use App\Models\ContentModeration;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class VerificationController extends Controller
{
    public function submitPhoto(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'photo' => 'required|image|mimes:jpeg,png,jpg|max:5120', // 5MB max
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

        // Upload verification photo
        $file = $request->file('photo');
        $filename = 'verification_' . uniqid() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('verification-photos', $filename, 'public');

        // Create content moderation entry
        $verification = ContentModeration::create([
            'user_id' => $user->id,
            'content_type' => 'photo',
            'content_url' => Storage::url($path),
            'status' => 'pending',
            'ai_score' => rand(70, 95) / 100, // Mock AI score
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Photo submitted for verification',
            'data' => [
                'id' => $verification->id,
                'status' => $verification->status,
                'submitted_at' => $verification->created_at,
            ]
        ]);
    }

    public function getStatus(Request $request): JsonResponse
    {
        // In real app, get user from token
        $user = UserProfile::where('username', 'demo')->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        $verification = ContentModeration::where('user_id', $user->id)
            ->where('content_type', 'photo')
            ->latest()
            ->first();

        $status = [
            'is_verified' => $user->is_verified,
            'status' => $verification ? $verification->status : 'not_submitted',
            'reason' => $verification && $verification->status === 'rejected'
                ? $verification->admin_notes
                : null,
        ];

        return response()->json([
            'success' => true,
            'data' => $status
        ]);
    }
}
