<?php

namespace App\Http\Controllers\Api;

//use App\Http\Controllers\Controller;
use App\Models\UserProfile;
use App\Models\UserPhoto;
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
            'photo' => 'required|file|mimes:jpeg,png,jpg,heic,heif,webp|max:20480', // 20MB max
            'order' => 'nullable|integer|min:0|max:5',
            'is_primary' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => implode(",", $validator->errors()->all()),
            ], 422);
        }

        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated'
            ], 401);
        }

        // Check if user already has 6 photos
        $currentPhotosCount = UserPhoto::where('user_id', $user->id)->count();
        if ($currentPhotosCount >= 6) {
            return response()->json([
                'success' => false,
                'message' => 'Maximum 6 photos allowed'
            ], 400);
        }

        // Upload photo
        $file = $request->file('photo');
        $extension = strtolower($file->getClientOriginalExtension());

        // Convert HEIC/HEIF to JPEG for compatibility
        if (in_array($extension, ['heic', 'heif'])) {
            $extension = 'jpg';
        }

        $filename = uniqid() . '.' . $extension;
        $path = $file->storeAs('profile-photos', $filename, 'public');

        $isPrimary = $request->boolean('is_primary', $currentPhotosCount === 0);

        // If setting as primary, unset other primary photos
        if ($isPrimary) {
            UserPhoto::where('user_id', $user->id)->update(['is_primary' => false]);
        }

        // Create photo record
        $photo = UserPhoto::create([
            'user_id' => $user->id,
            'photo_url' => Storage::url($path),
            'order' => $request->order ?? $currentPhotosCount,
            'is_primary' => $isPrimary,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Photo uploaded successfully',
            'data' => $photo
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

        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated'
            ], 401);
        }

        $photo = UserPhoto::where('id', $photoId)
            ->where('user_id', $user->id)
            ->first();

        if (!$photo) {
            return response()->json([
                'success' => false,
                'message' => 'Photo not found'
            ], 404);
        }

        // Update photo data
        if ($request->has('order')) {
            $photo->order = $request->order;
        }

        if ($request->has('is_primary')) {
            if ($request->boolean('is_primary')) {
                // If setting as primary, unset other primary photos
                UserPhoto::where('user_id', $user->id)
                    ->where('id', '!=', $photoId)
                    ->update(['is_primary' => false]);
                $photo->is_primary = true;
            } else {
                $photo->is_primary = false;
            }
        }

        $photo->save();

        return response()->json([
            'success' => true,
            'message' => 'Photo updated successfully',
            'data' => $photo
        ]);
    }

    public function destroy(Request $request, $photoId): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated'
            ], 401);
        }

        $photo = UserPhoto::where('id', $photoId)
            ->where('user_id', $user->id)
            ->first();

        if (!$photo) {
            return response()->json([
                'success' => false,
                'message' => 'Photo not found'
            ], 404);
        }

        // Delete file from storage
        $photoPath = str_replace('/storage/', '', $photo->photo_url);
        if (Storage::disk('public')->exists($photoPath)) {
            Storage::disk('public')->delete($photoPath);
        }

        // Delete photo record
        $photo->delete();

        return response()->json([
            'success' => true,
            'message' => 'Photo deleted successfully'
        ]);
    }
}
