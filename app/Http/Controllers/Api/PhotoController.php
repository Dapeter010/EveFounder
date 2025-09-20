<?php

namespace App\Http\Controllers\Api;

//use App\Http\Controllers\Controller;
use App\Models\UserProfile;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class PhotoController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'photo' => 'required|image|mimes:jpeg,png,jpg|max:5120', // 5MB max
            'order' => 'nullable|integer|min:0|max:5',
            'is_primary' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => implode(",", $validator->errors()->all()),
            ], 422);
        }

        // In real app, get user from token
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated'
            ], 404);
        }

        // Check if user already has 6 photos
        Log::info($user);
        $currentPhotos = $user->user_profile->photos ?? [];
        if (count($currentPhotos) >= 6) {
            return response()->json([
                'success' => false,
                'message' => 'Maximum 6 photos allowed'
            ], 400);
        }

        // Upload photo
        $file = $request->file('photo');
        $filename = uniqid() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('profile-photos', $filename, 'public');

        $photoData = [
            'url' => Storage::url($path),
            'filename' => $filename,
            'order' => $request->order ?? count($currentPhotos),
            'is_primary' => $request->boolean('is_primary', count($currentPhotos) === 0),
            'uploaded_at' => now()->toISOString(),
        ];
        Log::info($currentPhotos);
        Log::info($photoData);
        // Add to user's photos array
        array_push($currentPhotos, $photoData);

//        $currentPhotos[] = $photoData;
        $user->update(['photos' => $currentPhotos]);
        $user->userProfile()->update(['photos' => $currentPhotos]);

        return response()->json([
            'success' => true,
            'message' => 'Photo uploaded successfully',
            'data' => $photoData
        ]);
    }

    public function update(Request $request, $photoId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'order' => 'nullable|integer|min:0|max:5',
            'is_primary' => 'nullable|boolean',
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

        $photos = $user->photos ?? [];
        $photoIndex = collect($photos)->search(function ($photo) use ($photoId) {
            return isset($photo['id']) && $photo['id'] == $photoId;
        });

        if ($photoIndex === false) {
            return response()->json([
                'success' => false,
                'message' => 'Photo not found'
            ], 404);
        }

        // Update photo data
        if ($request->has('order')) {
            $photos[$photoIndex]['order'] = $request->order;
        }

        if ($request->has('is_primary')) {
            // If setting as primary, unset other primary photos
            if ($request->boolean('is_primary')) {
                foreach ($photos as &$photo) {
                    $photo['is_primary'] = false;
                }
                $photos[$photoIndex]['is_primary'] = true;
            } else {
                $photos[$photoIndex]['is_primary'] = false;
            }
        }

        $user->update(['photos' => $photos]);

        return response()->json([
            'success' => true,
            'message' => 'Photo updated successfully',
            'data' => $photos[$photoIndex]
        ]);
    }

    public function destroy(Request $request, $photoId): JsonResponse
    {
        // In real app, get user from token
        $user = UserProfile::where('username', 'demo')->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        $photos = $user->photos ?? [];
        $photoIndex = collect($photos)->search(function ($photo) use ($photoId) {
            return isset($photo['id']) && $photo['id'] == $photoId;
        });

        if ($photoIndex === false) {
            return response()->json([
                'success' => false,
                'message' => 'Photo not found'
            ], 404);
        }

        // Delete file from storage
        $photo = $photos[$photoIndex];
        if (isset($photo['filename'])) {
            Storage::disk('public')->delete('profile-photos/' . $photo['filename']);
        }

        // Remove from array
        unset($photos[$photoIndex]);
        $photos = array_values($photos); // Re-index array

        $user->update(['photos' => $photos]);

        return response()->json([
            'success' => true,
            'message' => 'Photo deleted successfully'
        ]);
    }
}
