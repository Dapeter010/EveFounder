<?php

namespace App\Http\Controllers\Api;

//use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;


class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                // Basic Info
                'firstName' => 'required|string|max:255',
                'lastName' => 'required|string|max:255',
                'username' => 'required|string|min:3|max:50|unique:user_profiles,username',
                'email' => 'required|email|max:255|unique:users,email',
                'phoneNumber' => 'required|string|max:20',
                'password' => 'required|string|min:8',
                'confirmPassword' => 'required|string|same:password',

                // Demographics
                'dateOfBirth' => 'required|date|before:18 years ago',
                'gender' => 'required|in:male,female,non-binary,other',
                'sexualOrientation' => 'required|in:straight,gay,lesbian,bisexual,pansexual,asexual,other',
                'location' => 'required|string|max:255',
                'state' => 'required|string|max:255',
                'country' => 'required|string|max:255',
                'latitude' => 'nullable|numeric',
                'longitude' => 'nullable|numeric',

                // Match Preferences
                'preferredGenders' => 'required|array|min:1',
                'preferredGenders.*' => 'string|in:male,female,non-binary,other',
                'preferredAgeRange' => 'required|array|size:2',
                'preferredAgeRange.*' => 'integer|min:18|max:65',
                'preferredDistance' => 'required|integer|min:1|max:500',
                'relationshipGoals' => 'required|in:casual,long-term,marriage,friendship,other',

                // Appearance
                'height' => 'required|integer|min:100|max:250',
                'bodyType' => 'required|in:slim,athletic,average,curvy,plus-size,muscular',
                'ethnicity' => 'required|string|max:255',
                'hairColor' => 'required|in:black,brown,blonde,red,gray,white,other',
                'eyeColor' => 'required|in:brown,blue,green,hazel,gray,other',

                // Lifestyle
                'educationLevel' => 'required|in:high-school,some-college,bachelors,masters,phd,trade-school,other',
                'occupation' => 'required|string|max:255',
                'religion' => 'required|in:christian,muslim,jewish,hindu,buddhist,atheist,agnostic,spiritual,other',
                'drinkingHabits' => 'required|in:never,rarely,socially,regularly,prefer-not-to-say',
                'smokingHabits' => 'required|in:never,rarely,socially,regularly,trying-to-quit,prefer-not-to-say',
                'exerciseFrequency' => 'required|in:never,rarely,sometimes,regularly,daily',

                // Interests & Personality
                'interests' => 'required|array|min:3',
                'interests.*' => 'string|max:50',
                'bio' => 'required|string|min:50|max:500',
                'perfectFirstDate' => 'required|string|min:20|max:1000',
                'favoriteWeekend' => 'required|string|min:20|max:1000',
                'surprisingFact' => 'required|string|min:20|max:1000',

                // Photos
                'photo_0' => 'required|image|mimes:jpeg,png,jpg|max:5120', // 5MB max
                'photo_1' => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
                'photo_2' => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
                'photo_3' => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
                'photo_4' => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
                'photo_5' => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => implode(",", $validator->errors()->all()),
                ], 422);
            }


            // Handle photo uploads
            $photos = [];
            for ($i = 0; $i < 6; $i++) {
                if ($request->hasFile("photo_$i")) {
                    $file = $request->file("photo_$i");
                    $filename = uniqid() . '.' . $file->getClientOriginalExtension();
                    $path = $file->storeAs('profile-photos', $filename, 'public');

                    $photos[] = [
                        'url' => Storage::url($path),
                        'filename' => $filename,
                        'order' => $i,
                        'is_primary' => $i === 0,
                        'uploaded_at' => now()->toISOString(),
                    ];
                }
            }

            // Generate a unique user ID (in real app, this would come from Supabase)
            $userId = \Illuminate\Support\Str::uuid();

            // Create user in users table
            $user = User::create([
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'first_name' => $request->firstName,
                'last_name' => $request->lastName,
                'date_of_birth' => $request->dateOfBirth,
                'gender' => $request->gender,
                'location' => $request->location,
                'bio' => $request->bio,
                'interests' => $request->interests,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'height' => $request->height,
                'education' => $request->educationLevel,
                'profession' => $request->occupation,
                'relationship_type' => $request->relationshipGoals,
            ]);

            $userProfile = UserProfile::create([
                'user_id' => $user->id,
                'first_name' => $request->firstName,
                'last_name' => $request->lastName,
                'username' => $request->username,
                'phone_number' => $request->phoneNumber,
                'date_of_birth' => $request->dateOfBirth,
                'gender' => $request->gender,
                'sexual_orientation' => $request->sexualOrientation,
                'location' => $request->location,
                'state' => $request->state,
                'country' => $request->country,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'preferred_genders' => $request->preferredGenders,
                'preferred_age_range' => $request->preferredAgeRange,
                'preferred_distance' => $request->preferredDistance,
                'relationship_goals' => $request->relationshipGoals,
                'height' => $request->height,
                'body_type' => $request->bodyType,
                'ethnicity' => $request->ethnicity,
                'hair_color' => $request->hairColor,
                'eye_color' => $request->eyeColor,
                'education_level' => $request->educationLevel,
                'occupation' => $request->occupation,
                'religion' => $request->religion,
                'drinking_habits' => $request->drinkingHabits,
                'smoking_habits' => $request->smokingHabits,
                'exercise_frequency' => $request->exerciseFrequency,
                'interests' => $request->interests,
                'bio' => $request->bio,
                'perfect_first_date' => $request->perfectFirstDate,
                'favorite_weekend' => $request->favoriteWeekend,
                'surprising_fact' => $request->surprisingFact,
                'photos' => $photos,
                'registration_date' => now(),
                'last_active_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'User registered successfully',
                'data' => [
                    'user' => $user->load('photos', 'preferences', 'subscription'),
                    'token' => 'mock-token-' . $user->id,
                ]
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 403);
        }
    }

    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'emailOrUsername' => 'required|string',
            'password' => 'required',
        ], [
            'emailOrUsername.required' => 'Email or username field cannot be blank',
            'password.required' => 'Password field cannot be blank',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => implode(",", $validator->errors()->all()),
            ], 422);
        }

        $loginField = $request->emailOrUsername;

        // Find user by email or username
        $user = null;
        if (filter_var($loginField, FILTER_VALIDATE_EMAIL)) {
            $user = User::where('email', $loginField)->first();
        } else {
            $userProfile = UserProfile::where('username', $loginField)->first();
            if ($userProfile) {
                $user = User::find($userProfile->user_id);
            }
        }

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials'
            ], 401);
        }

        if (!$user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Account is deactivated'
            ], 403);
        }

        // Revoke all existing tokens for this user (optional - for single session)
        // $user->tokens()->delete();

        // Create new auth token
        $token = $user->createToken('authToken')->plainTextToken;

        // Update last active
        $user->update(['last_active_at' => now()]);

        // Manually authenticate the user for this session
        Auth::login($user);

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'user' => $user->load('photos', 'preferences', 'subscription'),
                'token' => $token,
            ]
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully'
        ]);
    }

    public function me(Request $request): JsonResponse
    {


        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        $userData = $user->load('photos', 'userProfile', 'subscription')->toArray();

        // Map snake_case to camelCase
        $userData['firstName'] = $userData['first_name'] ?? null;
        $userData['lastName'] = $userData['last_name'] ?? null;
        $userData['preferences'] = $userData['userProfile'];
        // Optionally remove the old snake_case keys if frontend doesn’t need them
//        unset($userData['first_name'], $userData['last_name']);

        return response()->json([
            'success' => true,
            'data' => $userData
        ]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $user = Auth::user();


        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'bio' => 'sometimes|string|max:1000',
            'interests' => 'sometimes|array',
            'interests.*' => 'string|max:50',
            'location' => 'sometimes|string|max:255',
            'height' => 'sometimes|integer|min:100|max:250',
            'occupation' => 'sometimes|string|max:255',
            'relationship_goals' => 'sometimes|in:casual,long-term,marriage,friendship,other',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => implode(",", $validator->errors()->all()),
            ], 422);
        }

        $user->update($request->only([
            'first_name',
            'last_name',
            'bio',
            'interests',
            'location',
            'height',
            'education',
            'profession',
            'relationship_type',
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data' => $user->fresh()->load('photos', 'preferences', 'subscription')
        ]);
    }
}
