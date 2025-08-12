<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\UserProfile;
use App\Models\Like;
use App\Models\Report;
use App\Models\ProfileBoost;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create sample user profiles
        $userProfiles = [
            [
                'user_id' => (string) Str::uuid(),
                'first_name' => 'Emma',
                'last_name' => 'Thompson',
                'username' => 'emma_thompson',
                'phone_number' => '+44 7123 456789',
                'date_of_birth' => '1996-03-15',
                'gender' => 'female',
                'sexual_orientation' => 'straight',
                'location' => 'London',
                'state' => 'England',
                'country' => 'United Kingdom',
                'latitude' => 51.5074,
                'longitude' => -0.1278,
                'preferred_genders' => ['male'],
                'preferred_age_range' => [25, 35],
                'preferred_distance' => 25,
                'relationship_goals' => 'long-term',
                'height' => 165,
                'body_type' => 'athletic',
                'ethnicity' => 'White British',
                'hair_color' => 'brown',
                'eye_color' => 'blue',
                'education_level' => 'bachelors',
                'occupation' => 'Marketing Manager',
                'religion' => 'agnostic',
                'drinking_habits' => 'socially',
                'smoking_habits' => 'never',
                'exercise_frequency' => 'regularly',
                'interests' => ['Travel', 'Food', 'Photography', 'Music'],
                'bio' => 'Love traveling and trying new restaurants. Looking for someone to explore London with!',
                'perfect_first_date' => 'A cozy coffee shop where we can talk for hours and get to know each other.',
                'favorite_weekend' => 'Exploring new neighborhoods in London or planning my next travel adventure.',
                'surprising_fact' => 'I can speak three languages fluently and have visited over 20 countries!',
                'photos' => [
                    [
                        'url' => 'https://images.pexels.com/photos/774909/pexels-photo-774909.jpeg?auto=compress&cs=tinysrgb&w=800',
                        'order' => 0,
                        'is_primary' => true,
                        'uploaded_at' => now()->toISOString(),
                    ]
                ],
                'registration_date' => now()->subDays(30),
                'last_active_at' => now(),
                'is_verified' => true,
            ],
            [
                'user_id' => (string) Str::uuid(),
                'first_name' => 'Sophie',
                'last_name' => 'Davis',
                'username' => 'sophie_davis',
                'phone_number' => '+44 7234 567890',
                'date_of_birth' => '1998-07-22',
                'gender' => 'female',
                'sexual_orientation' => 'straight',
                'location' => 'Manchester',
                'state' => 'England',
                'country' => 'United Kingdom',
                'latitude' => 53.4808,
                'longitude' => -2.2426,
                'preferred_genders' => ['male'],
                'preferred_age_range' => [24, 32],
                'preferred_distance' => 30,
                'relationship_goals' => 'long-term',
                'height' => 170,
                'body_type' => 'slim',
                'ethnicity' => 'White British',
                'hair_color' => 'blonde',
                'eye_color' => 'green',
                'education_level' => 'masters',
                'occupation' => 'Software Engineer',
                'religion' => 'atheist',
                'drinking_habits' => 'rarely',
                'smoking_habits' => 'never',
                'exercise_frequency' => 'daily',
                'bio' => 'Fitness enthusiast and coffee lover. Always up for an adventure!',
                'interests' => ['Fitness', 'Coffee', 'Hiking', 'Movies'],
                'perfect_first_date' => 'A hiking trail followed by coffee and good conversation.',
                'favorite_weekend' => 'Early morning workout, brunch with friends, and exploring new coffee shops.',
                'surprising_fact' => 'I built my first mobile app when I was 16 and it got 10,000 downloads!',
                'photos' => [
                    [
                        'url' => 'https://images.pexels.com/photos/1542085/pexels-photo-1542085.jpeg?auto=compress&cs=tinysrgb&w=800',
                        'order' => 0,
                        'is_primary' => true,
                        'uploaded_at' => now()->toISOString(),
                    ]
                ],
                'registration_date' => now()->subDays(20),
                'last_active_at' => now()->subHours(2),
                'is_verified' => true,
            ],
            [
                'user_id' => (string) Str::uuid(),
                'first_name' => 'Jessica',
                'last_name' => 'Wilson',
                'username' => 'jessica_wilson',
                'phone_number' => '+44 7345 678901',
                'date_of_birth' => '1994-11-08',
                'gender' => 'female',
                'sexual_orientation' => 'straight',
                'location' => 'Birmingham',
                'state' => 'England',
                'country' => 'United Kingdom',
                'latitude' => 52.4862,
                'longitude' => -1.8904,
                'preferred_genders' => ['male'],
                'preferred_age_range' => [28, 38],
                'preferred_distance' => 20,
                'relationship_goals' => 'marriage',
                'height' => 168,
                'body_type' => 'average',
                'ethnicity' => 'White British',
                'hair_color' => 'black',
                'eye_color' => 'brown',
                'education_level' => 'bachelors',
                'occupation' => 'Graphic Designer',
                'religion' => 'spiritual',
                'drinking_habits' => 'socially',
                'smoking_habits' => 'never',
                'exercise_frequency' => 'sometimes',
                'bio' => 'Art lover and weekend dancer. Looking for genuine connections.',
                'interests' => ['Art', 'Dancing', 'Wine', 'Books'],
                'perfect_first_date' => 'An art gallery opening followed by dinner at a cozy restaurant.',
                'favorite_weekend' => 'Dancing classes, visiting art exhibitions, and reading with a glass of wine.',
                'surprising_fact' => 'I once performed in a flash mob in Times Square during my trip to New York!',
                'photos' => [
                    [
                        'url' => 'https://images.pexels.com/photos/1239291/pexels-photo-1239291.jpeg?auto=compress&cs=tinysrgb&w=800',
                        'order' => 0,
                        'is_primary' => true,
                        'uploaded_at' => now()->toISOString(),
                    ]
                ],
                'registration_date' => now()->subDays(45),
                'last_active_at' => now()->subDays(1),
                'is_verified' => false,
            ],
        ];

        foreach ($userProfiles as $userData) {
            UserProfile::create($userData);
        }

        // Create a test male user
        $maleUser = UserProfile::create([
            'user_id' => 'demo-user-john',
            'first_name' => 'John',
            'last_name' => 'Smith',
            'username' => 'john_smith',
            'phone_number' => '+44 7456 789012',
            'date_of_birth' => '1992-05-10',
            'gender' => 'male',
            'sexual_orientation' => 'straight',
            'location' => 'London',
            'state' => 'England',
            'country' => 'United Kingdom',
            'latitude' => 51.5074,
            'longitude' => -0.1278,
            'preferred_genders' => ['female'],
            'preferred_age_range' => [22, 32],
            'preferred_distance' => 30,
            'relationship_goals' => 'long-term',
            'height' => 180,
            'body_type' => 'athletic',
            'ethnicity' => 'White British',
            'hair_color' => 'brown',
            'eye_color' => 'blue',
            'education_level' => 'masters',
            'occupation' => 'Software Developer',
            'religion' => 'agnostic',
            'drinking_habits' => 'socially',
            'smoking_habits' => 'never',
            'exercise_frequency' => 'regularly',
            'interests' => ['Technology', 'Hiking', 'Coffee', 'Travel'],
            'bio' => 'Tech enthusiast and weekend hiker. Love good coffee and great conversations on EveFound.',
            'perfect_first_date' => 'A nice walk in Hyde Park followed by coffee and getting to know each other.',
            'favorite_weekend' => 'Hiking in the countryside or exploring London\'s tech meetups and coffee shops.',
            'surprising_fact' => 'I once climbed Mount Kilimanjaro and coded an app at the summit!',
            'photos' => [
                [
                    'url' => 'https://images.pexels.com/photos/220453/pexels-photo-220453.jpeg?auto=compress&cs=tinysrgb&w=800',
                    'order' => 0,
                    'is_primary' => true,
                    'uploaded_at' => now()->toISOString(),
                ]
            ],
            'registration_date' => now()->subDays(60),
            'last_active_at' => now(),
            'is_verified' => true,
        ]);

        // Create demo user for testing
        UserProfile::create([
            'user_id' => 'demo-user-demo',
            'first_name' => 'Demo',
            'last_name' => 'User',
            'username' => 'demo',
            'phone_number' => '+44 7000 000000',
            'date_of_birth' => '1995-01-01',
            'gender' => 'male',
            'sexual_orientation' => 'straight',
            'location' => 'London',
            'state' => 'England',
            'country' => 'United Kingdom',
            'latitude' => 51.5074,
            'longitude' => -0.1278,
            'preferred_genders' => ['female'],
            'preferred_age_range' => [22, 32],
            'preferred_distance' => 25,
            'relationship_goals' => 'long-term',
            'height' => 180,
            'body_type' => 'athletic',
            'ethnicity' => 'White British',
            'hair_color' => 'brown',
            'eye_color' => 'blue',
            'education_level' => 'bachelors',
            'occupation' => 'Software Developer',
            'religion' => 'agnostic',
            'drinking_habits' => 'socially',
            'smoking_habits' => 'never',
            'exercise_frequency' => 'regularly',
            'interests' => ['Technology', 'Travel', 'Fitness'],
            'bio' => 'Tech enthusiast who loves to travel and stay fit. Looking for meaningful connections.',
            'perfect_first_date' => 'A nice coffee shop where we can talk and get to know each other.',
            'favorite_weekend' => 'Exploring new places or relaxing at home with a good book.',
            'surprising_fact' => 'I can solve a Rubik\'s cube in under 2 minutes!',
            'photos' => [],
            'registration_date' => now()->subDays(1),
            'last_active_at' => now(),
            'is_verified' => false,
        ]);

        // Create some sample likes and matches
        $users = UserProfile::all();

        foreach ($users as $user) {
            // Create some random likes
            $otherUsers = $users->where('user_id', '!=', $user->user_id)->random(min(3, $users->count() - 1));

            foreach ($otherUsers as $otherUser) {
                Like::create([
                    'liker_id' => $user->user_id,
                    'liked_id' => $otherUser->user_id,
                    'is_super_like' => rand(0, 10) > 8, // 20% chance of super like
                    'status' => 'pending',
                    'expires_at' => now()->addDays(30),
                ]);
            }
        }

        // Create some sample reports
        if ($users->count() >= 2) {
            Report::create([
                'reporter_id' => $users->first()->user_id,
                'reported_id' => $users->last()->user_id,
                'type' => 'inappropriate_behavior',
                'reason' => 'Sending inappropriate messages',
                'description' => 'This user has been sending explicit messages despite being told to stop.',
                'status' => 'pending',
            ]);
        }

        // Create some sample profile boosts
        ProfileBoost::create([
            'user_id' => $maleUser->user_id,
            'boost_type' => 'profile',
            'cost' => 4.99,
            'starts_at' => now()->subHours(2),
            'ends_at' => now()->subHours(1.5),
            'views_gained' => 156,
            'likes_gained' => 23,
            'matches_gained' => 4,
            'status' => 'completed',
        ]);
    }
}
