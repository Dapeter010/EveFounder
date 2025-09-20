<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    private $ukCities = [
        ['name' => 'London', 'state' => 'England', 'lat' => 51.5074, 'lng' => -0.1278],
        ['name' => 'Manchester', 'state' => 'England', 'lat' => 53.4808, 'lng' => -2.2426],
        ['name' => 'Birmingham', 'state' => 'England', 'lat' => 52.4862, 'lng' => -1.8904],
        ['name' => 'Leeds', 'state' => 'England', 'lat' => 53.8008, 'lng' => -1.5491],
        ['name' => 'Glasgow', 'state' => 'Scotland', 'lat' => 55.8642, 'lng' => -4.2518],
        ['name' => 'Liverpool', 'state' => 'England', 'lat' => 53.4084, 'lng' => -2.9916],
        ['name' => 'Newcastle', 'state' => 'England', 'lat' => 54.9783, 'lng' => -1.6178],
        ['name' => 'Sheffield', 'state' => 'England', 'lat' => 53.3811, 'lng' => -1.4701],
        ['name' => 'Bristol', 'state' => 'England', 'lat' => 51.4545, 'lng' => -2.5879],
        ['name' => 'Cardiff', 'state' => 'Wales', 'lat' => 51.4816, 'lng' => -3.1791],
        ['name' => 'Edinburgh', 'state' => 'Scotland', 'lat' => 55.9533, 'lng' => -3.1883],
        ['name' => 'Leicester', 'state' => 'England', 'lat' => 52.6369, 'lng' => -1.1398],
        ['name' => 'Coventry', 'state' => 'England', 'lat' => 52.4068, 'lng' => -1.5197],
        ['name' => 'Bradford', 'state' => 'England', 'lat' => 53.7960, 'lng' => -1.7594],
        ['name' => 'Belfast', 'state' => 'Northern Ireland', 'lat' => 54.5973, 'lng' => -5.9301],
        ['name' => 'Nottingham', 'state' => 'England', 'lat' => 52.9548, 'lng' => -1.1581],
        ['name' => 'Plymouth', 'state' => 'England', 'lat' => 50.3755, 'lng' => -4.1427],
        ['name' => 'Stoke-on-Trent', 'state' => 'England', 'lat' => 53.0027, 'lng' => -2.1794],
        ['name' => 'Wolverhampton', 'state' => 'England', 'lat' => 52.5864, 'lng' => -2.1285],
        ['name' => 'Derby', 'state' => 'England', 'lat' => 52.9225, 'lng' => -1.4746],
    ];

    private $firstNames = [
        'male' => ['James', 'Oliver', 'George', 'Harry', 'Jack', 'Jacob', 'Noah', 'Charlie', 'Muhammad', 'Thomas', 'Oscar', 'William', 'Leo', 'Arthur', 'Henry', 'Freddie', 'Archie', 'Theodore', 'Joshua', 'Alexander'],
        'female' => ['Olivia', 'Amelia', 'Isla', 'Ava', 'Mia', 'Grace', 'Sophia', 'Isabella', 'Lily', 'Freya', 'Emily', 'Poppy', 'Ella', 'Charlotte', 'Harper', 'Evie', 'Florence', 'Chloe', 'Willow', 'Phoebe'],
        'non-binary' => ['Riley', 'Jordan', 'Taylor', 'Alex', 'Jamie', 'Casey', 'Quinn', 'Sage', 'River', 'Phoenix']
    ];

    private $lastNames = ['Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Garcia', 'Miller', 'Davis', 'Rodriguez', 'Martinez', 'Hernandez', 'Lopez', 'Gonzalez', 'Wilson', 'Anderson', 'Thomas', 'Taylor', 'Moore', 'Jackson', 'Martin', 'Lee', 'Perez', 'Thompson', 'White', 'Harris', 'Sanchez', 'Clark', 'Ramirez', 'Lewis', 'Robinson'];

    private $interests = [
        'Travel', 'Photography', 'Cooking', 'Fitness', 'Reading', 'Music', 'Dancing', 'Art', 'Movies', 'Gaming',
        'Hiking', 'Yoga', 'Swimming', 'Running', 'Cycling', 'Tennis', 'Football', 'Rugby', 'Cricket', 'Golf',
        'Wine Tasting', 'Coffee', 'Gardening', 'Fashion', 'Technology', 'Science', 'History', 'Politics', 'Animals', 'Nature',
        'Theater', 'Comedy', 'Concerts', 'Festivals', 'Food', 'Meditation', 'Volunteering', 'Learning Languages', 'Board Games', 'Chess'
    ];

    private $professions = [
        'Software Engineer', 'Teacher', 'Doctor', 'Nurse', 'Lawyer', 'Accountant', 'Marketing Manager', 'Designer',
        'Consultant', 'Engineer', 'Sales Manager', 'Project Manager', 'Analyst', 'Developer', 'Researcher',
        'Architect', 'Chef', 'Writer', 'Photographer', 'Artist', 'Musician', 'Student', 'Entrepreneur',
        'Therapist', 'Pharmacist', 'Veterinarian', 'Dentist', 'Police Officer', 'Firefighter', 'Paramedic'
    ];

    private $educationLevels = ['high-school', 'some-college', 'bachelors', 'masters', 'phd', 'trade-school'];
    private $genders = ['male', 'female', 'non-binary'];
    private $sexualOrientations = ['straight', 'gay', 'lesbian', 'bisexual', 'pansexual'];
    private $relationshipGoals = ['casual', 'long-term', 'marriage', 'friendship', 'other'];
    private $relationshipType = ['casual', 'serious-relationship', 'marriage', 'friends'];
    private $bodyTypes = ['slim', 'athletic', 'average', 'curvy', 'plus-size', 'muscular'];
    private $ethnicities = ['White British', 'Black African', 'Black Caribbean', 'Indian', 'Pakistani', 'Bangladeshi', 'Chinese', 'Mixed', 'Other Asian', 'Arab'];
    private $hairColors = ['black', 'brown', 'blonde', 'red', 'gray'];
    private $eyeColors = ['brown', 'blue', 'green', 'hazel', 'gray'];
    private $religions = ['christian', 'muslim', 'jewish', 'hindu', 'buddhist', 'atheist', 'agnostic', 'spiritual'];
    private $drinkingHabits = ['never', 'rarely', 'socially', 'regularly'];
    private $smokingHabits = ['never', 'rarely', 'socially', 'trying-to-quit'];
    private $exerciseFrequencies = ['rarely', 'sometimes', 'regularly', 'daily'];

    public function run()
    {
        // Clear existing data
        $this->clearTables();

        // Create admin user
        $this->createAdminUser();

        // Create 30 regular users
        for ($i = 1; $i <= 30; $i++) {
            $this->createUser($i);
        }

        // Create some interactions
        $this->createInteractions();

        // Add platform settings
        $this->createPlatformSettings();

        echo "✅ Database seeded successfully with 31 users (1 admin + 30 regular users)\n";
        echo "🔐 Admin login: admin@evefound.com / password123\n";
        echo "👤 User logins: user1@evefound.com to user30@evefound.com / password123\n";
    }

    private function clearTables()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        $tables = [
            'messages', 'matches', 'likes', 'profile_views', 'notifications',
            'user_photos', 'user_preferences', 'user_profiles', 'users',
            'subscriptions', 'profile_boosts', 'reports', 'blocked_users'
        ];

        foreach ($tables as $table) {
            DB::table($table)->truncate();
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    private function createAdminUser()
    {
        $user = DB::table('users')->insertGetId([
            'email' => 'admin@evefound.com',
            'password' => Hash::make('password123'),
            'first_name' => 'Admin',
            'last_name' => 'User',
            'uid' => (string)\Illuminate\Support\Str::uuid(),
            'date_of_birth' => '1990-01-01',
            'gender' => 'prefer-not-to-say',
            'location' => 'London',
            'bio' => 'Platform administrator',
            'is_verified' => true,
            'is_active' => true,
            'is_admin' => true,
            'last_active_at' => now(),
            'latitude' => 51.5074,
            'longitude' => -0.1278,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create basic profile for admin
        DB::table('user_profiles')->insert([
            'user_id' => $user,
            'first_name' => 'Admin',
            'last_name' => 'User',
            'username' => 'admin',
            'date_of_birth' => '1990-01-01',
            'gender' => 'other',
            'sexual_orientation' => 'other',
            'location' => 'London',
            'state' => 'England',
            'latitude' => 51.5074,
            'longitude' => -0.1278,
            'preferred_genders' => json_encode(['male', 'female']),
            'preferred_age_range' => json_encode([25, 45]),
            'relationship_goals' => 'other',
            'registration_date' => now(),
            'is_verified' => true,
            'is_active' => true,
            'last_active_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create preferences for admin
        DB::table('user_preferences')->insert([
            'user_id' => $user,
            'min_age' => 25,
            'max_age' => 45,
            'max_distance' => 50,
            'interested_genders' => json_encode(['male', 'female']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createUser($index)
    {
        $city = $this->ukCities[array_rand($this->ukCities)];
        $gender = $this->genders[array_rand($this->genders)];
        $firstName = $this->firstNames[$gender][array_rand($this->firstNames[$gender])];
        $lastName = $this->lastNames[array_rand($this->lastNames)];
        $username = strtolower($firstName . $lastName . rand(100, 999));
        $email = "user{$index}@evefound.com";

        // Generate realistic age (18-65)
        $age = rand(18, 65);
        $dateOfBirth = Carbon::now()->subYears($age)->subDays(rand(0, 365))->format('Y-m-d');

        // Generate height (150-200cm)
        $height = rand(150, 200);

        // Create main user record
        $userId = DB::table('users')->insertGetId([
            'email' => $email,
            'password' => Hash::make('password123'),
            'first_name' => $firstName,
            'last_name' => $lastName,
            'uid' => (string)\Illuminate\Support\Str::uuid(),
            'date_of_birth' => $dateOfBirth,
            'gender' => $gender,
            'location' => $city['name'],
            'bio' => $this->generateBio($firstName, $gender),
            'interests' => json_encode($this->getRandomInterests()),
            'is_verified' => rand(0, 100) > 30, // 70% verified
            'is_active' => rand(0, 100) > 10, // 90% active
            'is_admin' => false,
            'last_active_at' => $this->getRandomLastActive(),
            'latitude' => $city['lat'] + (rand(-100, 100) / 1000), // Add slight variation
            'longitude' => $city['lng'] + (rand(-100, 100) / 1000),
            'height' => $height,
            'profession' => $this->professions[array_rand($this->professions)],
            'relationship_type' => $this->relationshipType[array_rand($this->relationshipType)],
            'created_at' => $this->getRandomRegistrationDate(),
            'updated_at' => now(),
        ]);

        // Create detailed user profile
        $this->createUserProfile($userId, $firstName, $lastName, $username, $dateOfBirth, $gender, $city, $height);

        // Create user preferences
        $this->createUserPreferences($userId, $age, $gender);

        // Create user photos
        $this->createUserPhotos($userId);

        // Maybe create subscription (30% chance)
        if (rand(0, 100) < 30) {
            $this->createSubscription($userId);
        }

        // Maybe create profile boost (20% chance)
        if (rand(0, 100) < 20) {
            $this->createProfileBoost($userId);
        }
    }

    private function createUserProfile($userId, $firstName, $lastName, $username, $dateOfBirth, $gender, $city, $height)
    {
        $sexualOrientation = $this->getSexualOrientation($gender);
        $preferredGenders = $this->getPreferredGenders($gender, $sexualOrientation);

        DB::table('user_profiles')->insert([
            'user_id' => $userId,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'username' => $username,
            'phone_number' => $this->generatePhoneNumber(),
            'date_of_birth' => $dateOfBirth,
            'gender' => $gender,
            'sexual_orientation' => $sexualOrientation,
            'location' => $city['name'],
            'state' => $city['state'],
            'latitude' => $city['lat'] + (rand(-100, 100) / 1000),
            'longitude' => $city['lng'] + (rand(-100, 100) / 1000),
            'preferred_genders' => json_encode($preferredGenders),
            'preferred_age_range' => json_encode($this->getPreferredAgeRange()),
            'preferred_distance' => rand(10, 50),
            'relationship_goals' => $this->relationshipGoals[array_rand($this->relationshipGoals)],
            'height' => $height,
            'body_type' => $this->bodyTypes[array_rand($this->bodyTypes)],
            'ethnicity' => $this->ethnicities[array_rand($this->ethnicities)],
            'hair_color' => $this->hairColors[array_rand($this->hairColors)],
            'eye_color' => $this->eyeColors[array_rand($this->eyeColors)],
            'education_level' => $this->educationLevels[array_rand($this->educationLevels)],
            'occupation' => $this->professions[array_rand($this->professions)],
            'religion' => $this->religions[array_rand($this->religions)],
            'drinking_habits' => $this->drinkingHabits[array_rand($this->drinkingHabits)],
            'smoking_habits' => $this->smokingHabits[array_rand($this->smokingHabits)],
            'exercise_frequency' => $this->exerciseFrequencies[array_rand($this->exerciseFrequencies)],
            'interests' => json_encode($this->getRandomInterests()),
            'bio' => $this->generateDetailedBio($firstName, $gender),
            'perfect_first_date' => $this->generatePerfectFirstDate(),
            'favorite_weekend' => $this->generateFavoriteWeekend(),
            'surprising_fact' => $this->generateSurprisingFact(),
            'registration_date' => $this->getRandomRegistrationDate(),
            'is_verified' => rand(0, 100) > 30,
            'is_active' => rand(0, 100) > 10,
            'last_active_at' => $this->getRandomLastActive(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createUserPreferences($userId, $age, $gender)
    {
        $minAge = max(18, $age - rand(5, 15));
        $maxAge = min(65, $age + rand(5, 20));

        DB::table('user_preferences')->insert([
            'user_id' => $userId,
            'min_age' => $minAge,
            'max_age' => $maxAge,
            'max_distance' => rand(10, 100),
            'interested_genders' => json_encode($this->getInterestedGenders($gender)),
            'min_height' => rand(150, 170),
            'max_height' => rand(175, 200),
            'show_age' => rand(0, 100) > 20,
            'show_distance' => rand(0, 100) > 30,
            'show_online_status' => rand(0, 100) > 40,
            'show_read_receipts' => rand(0, 100) > 50,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createUserPhotos($userId)
    {
        $numPhotos = rand(2, 6);

        for ($i = 0; $i < $numPhotos; $i++) {
            DB::table('user_photos')->insert([
                'user_id' => $userId,
                'photo_url' => "https://picsum.photos/400/600?random=" . ($userId * 10 + $i),
                'order' => $i,
                'is_primary' => $i === 0,
                'is_verified' => rand(0, 100) > 20,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function createSubscription($userId)
    {
        $planType = rand(0, 1) ? 'basic' : 'premium';
        $amount = $planType === 'basic' ? 9.99 : 19.99;
        $startsAt = Carbon::now()->subDays(rand(0, 90));
        $endsAt = $startsAt->copy()->addMonth();

        DB::table('subscriptions')->insert([
            'user_id' => $userId,
            'plan_type' => $planType,
            'status' => 'active',
            'amount' => $amount,
            'currency' => 'GBP',
            'stripe_subscription_id' => 'sub_' . bin2hex(random_bytes(12)),
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'created_at' => $startsAt,
            'updated_at' => now(),
        ]);
    }

    private function createProfileBoost($userId)
    {
        $boostTypes = ['profile', 'super', 'weekend'];
        $costs = ['profile' => 4.99, 'super' => 9.99, 'weekend' => 14.99];
        $durations = ['profile' => 30, 'super' => 60, 'weekend' => 120]; // minutes

        $boostType = $boostTypes[array_rand($boostTypes)];
        $startsAt = Carbon::now()->subMinutes(rand(0, 1440)); // Within last 24 hours
        $endsAt = $startsAt->copy()->addMinutes($durations[$boostType]);

        DB::table('profile_boosts')->insert([
            'user_id' => $userId,
            'boost_type' => $boostType,
            'cost' => $costs[$boostType],
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'views_gained' => rand(10, 100),
            'likes_gained' => rand(0, 20),
            'matches_gained' => rand(0, 5),
            'status' => $endsAt->isPast() ? 'completed' : 'active',
            'created_at' => $startsAt,
            'updated_at' => now(),
        ]);
    }

    private function createInteractions()
    {
        $userIds = DB::table('users')->where('is_admin', false)->pluck('id')->toArray();

        echo "Creating interactions for " . count($userIds) . " users\n";

        // Create likes (200 likes)
        $likesCreated = 0;
        $attempts = 0;
        $maxAttempts = 500;

        while ($likesCreated < 200 && $attempts < $maxAttempts) {
            $likerId = $userIds[array_rand($userIds)];
            $likedId = $userIds[array_rand($userIds)];

            if ($likerId !== $likedId) {
                // Check if already exists
                $exists = DB::table('likes')
                    ->where('liker_id', $likerId)
                    ->where('liked_id', $likedId)
                    ->exists();

                if (!$exists) {
                    $isSuperLike = rand(0, 100) < 10; // 10% super likes
                    $createdAt = Carbon::now()->subDays(rand(0, 30))->addSeconds(rand(0, 86400));

                    try {
                        DB::table('likes')->insert([
                            'liker_id' => $likerId,
                            'liked_id' => $likedId,
                            'is_super_like' => $isSuperLike,
                            'status' => 'pending',
                            'expires_at' => $createdAt->copy()->addDays(30),
                            'created_at' => $createdAt,
                            'updated_at' => $createdAt,
                        ]);
                        $likesCreated++;
                    } catch (\Exception $e) {
                        // Skip on error
                    }
                }
            }
            $attempts++;
        }

        echo "Created {$likesCreated} likes\n";

        // Create matches from mutual likes
        $this->createMatches();

        // Create profile views
        $this->createProfileViews($userIds);

        // Create some messages
        $this->createMessages();
    }

    private function createMatches()
    {
        // Find mutual likes and create matches
        $mutualLikes = DB::select("
            SELECT l1.liker_id as user1_id, l1.liked_id as user2_id
            FROM likes l1
            INNER JOIN likes l2 ON l1.liker_id = l2.liked_id AND l1.liked_id = l2.liker_id
            WHERE l1.liker_id < l1.liked_id
            LIMIT 50
        ");

        foreach ($mutualLikes as $match) {
            $matchedAt = Carbon::now()->subDays(rand(0, 20));

            // Insert match
            $matchId = DB::table('matches')->insertGetId([
                'user1_id' => $match->user1_id,
                'user2_id' => $match->user2_id,
                'matched_at' => $matchedAt,
                'is_active' => true,
                'created_at' => $matchedAt,
                'updated_at' => $matchedAt,
            ]);

            // Update likes status
            DB::table('likes')
                ->where(function($q) use ($match) {
                    $q->where('liker_id', $match->user1_id)->where('liked_id', $match->user2_id);
                })
                ->orWhere(function($q) use ($match) {
                    $q->where('liker_id', $match->user2_id)->where('liked_id', $match->user1_id);
                })
                ->update(['status' => 'matched']);
        }
    }

    private function createProfileViews($userIds)
    {
        $attempts = 0;
        $created = 0;
        $maxAttempts = 1000;

        while ($created < 500 && $attempts < $maxAttempts) {
            $viewerId = $userIds[array_rand($userIds)];
            $viewedId = $userIds[array_rand($userIds)];

            if ($viewerId !== $viewedId) {
                // Create unique timestamp by adding random seconds and microseconds
                $baseTime = Carbon::now()->subDays(rand(0, 7));
                $viewedAt = $baseTime->addSeconds(rand(0, 86400))->addMicroseconds(rand(0, 999999));

                // Check if this combination already exists
                $exists = DB::table('profile_views')
                    ->where('viewer_id', $viewerId)
                    ->where('viewed_id', $viewedId)
                    ->where('viewed_at', $viewedAt)
                    ->exists();

                if (!$exists) {
                    try {
                        DB::table('profile_views')->insert([
                            'viewer_id' => $viewerId,
                            'viewed_id' => $viewedId,
                            'viewed_at' => $viewedAt,
                            'created_at' => $viewedAt,
                            'updated_at' => $viewedAt,
                        ]);
                        $created++;
                    } catch (\Exception $e) {
                        // Skip duplicate entries silently
                    }
                }
            }
            $attempts++;
        }

        echo "Created {$created} profile views\n";
    }

    private function createMessages()
    {
        $matches = DB::table('matches')->get();
        echo "Creating messages for " . count($matches) . " matches\n";

        foreach ($matches as $match) {
            if (rand(0, 100) < 70) { // 70% of matches have messages
                $messageCount = rand(1, 20);

                for ($i = 0; $i < $messageCount; $i++) {
                    $senderId = rand(0, 1) ? $match->user1_id : $match->user2_id;
                    $receiverId = $senderId === $match->user1_id ? $match->user2_id : $match->user1_id;

                    // Create unique timestamps for messages
                    $baseTime = Carbon::parse($match->matched_at);
                    $sentAt = $baseTime->addMinutes(rand(1, 10080) + ($i * 10))->addSeconds(rand(0, 600));

                    try {
                        DB::table('messages')->insert([
                            'match_id' => $match->id,
                            'sender_id' => $senderId,
                            'receiver_id' => $receiverId,
                            'content' => $this->generateMessage(),
                            'type' => 'text',
                            'read_at' => rand(0, 100) < 80 ? $sentAt->copy()->addMinutes(rand(1, 1440)) : null,
                            'is_deleted' => false,
                            'created_at' => $sentAt,
                            'updated_at' => $sentAt,
                        ]);
                    } catch (\Exception $e) {
                        // Skip on error and continue
                        continue;
                    }
                }
            }
        }
    }

    private function createPlatformSettings()
    {
        $settings = [
            ['key' => 'platform.minAge', 'value' => '18', 'type' => 'integer', 'description' => 'Minimum age for users'],
            ['key' => 'platform.maxAge', 'value' => '65', 'type' => 'integer', 'description' => 'Maximum age for users'],
            ['key' => 'platform.maxDistance', 'value' => '100', 'type' => 'integer', 'description' => 'Maximum distance in miles'],
            ['key' => 'platform.dailySuperLikes', 'value' => '5', 'type' => 'integer', 'description' => 'Daily super likes for free users'],
            ['key' => 'platform.maxPhotos', 'value' => '6', 'type' => 'integer', 'description' => 'Maximum photos per profile'],
            ['key' => 'billing.basicPrice', 'value' => '9.99', 'type' => 'float', 'description' => 'Basic plan price'],
            ['key' => 'billing.premiumPrice', 'value' => '19.99', 'type' => 'float', 'description' => 'Premium plan price'],
        ];

        foreach ($settings as $setting) {
            DB::table('platform_settings')->updateOrInsert(
                ['key' => $setting['key']],
                array_merge($setting, ['created_at' => now(), 'updated_at' => now()])
            );
        }
    }

    // Helper methods
    private function generateBio($name, $gender)
    {
        $bios = [
            "Hey! I'm {$name} and I love exploring new places and trying new foods. Looking for someone to share adventures with! 🌟",
            "Coffee enthusiast, book lover, and weekend hiker. Let's grab a coffee and see where the conversation takes us! ☕",
            "Life's too short for boring conversations. I'm into music, travel, and making people laugh. What's your story? 😊",
            "Passionate about fitness and healthy living. Love cooking, movies, and long walks. Looking for genuine connections! 💪",
            "Creative soul who loves art, photography, and good wine. Let's create some beautiful memories together! 🎨",
        ];

        return str_replace('{$name}', $name, $bios[array_rand($bios)]);
    }

    private function generateDetailedBio($name, $gender)
    {
        $templates = [
            "I'm a passionate person who believes in living life to the fullest. I love discovering new restaurants, exploring hidden gems in the city, and having deep conversations about everything from philosophy to the latest Netflix series. When I'm not working, you'll find me at the gym, trying out new recipes, or planning my next adventure. I'm looking for someone who shares my enthusiasm for life and isn't afraid to be themselves.",

            "Adventure seeker and coffee connoisseur! I believe the best relationships are built on friendship, laughter, and shared experiences. I'm equally happy hiking in the countryside or binge-watching a great series on a rainy Sunday. I value honesty, kindness, and a good sense of humor. Looking for someone who's ready for genuine connections and maybe some spontaneous road trips!",

            "I'm a creative soul who finds joy in the little things - a perfect sunset, a great book, or discovering a new song that gives me goosebumps. I love traveling, though I'm just as content exploring my own city. I'm passionate about my career but never let it overshadow the important things in life like family, friends, and making memories. Seeking someone who appreciates both quiet moments and exciting adventures."
        ];

        return $templates[array_rand($templates)];
    }

    private function generatePerfectFirstDate()
    {
        $dates = [
            "A cozy coffee shop where we can talk for hours without realizing time has passed.",
            "A walk through a local market, trying different foods and discovering new flavors together.",
            "Mini golf followed by dinner - something fun where we can be competitive but not too serious!",
            "A cooking class where we can laugh at our mistakes and hopefully create something delicious.",
            "An art gallery or museum followed by lunch and discussing what we've seen.",
            "A scenic hike with a picnic at the top - nothing beats good conversation with a view.",
            "Wine or beer tasting - learning something new while getting to know each other.",
            "A local festival or farmers market - lots to see and talk about in a relaxed setting."
        ];

        return $dates[array_rand($dates)];
    }

    private function generateFavoriteWeekend()
    {
        $weekends = [
            "Saturday morning yoga, brunch with friends, and a lazy afternoon reading in the park.",
            "Exploring a new neighborhood, trying a restaurant I've never been to, and maybe catching a movie.",
            "Hiking or cycling somewhere beautiful, followed by a well-deserved pub lunch.",
            "Sleeping in, making a big breakfast, and spending the day working on creative projects.",
            "Visiting galleries or museums, grabbing coffee, and people-watching in the city center.",
            "Beach or countryside trip - fresh air, good company, and getting away from city life.",
            "Cooking an elaborate dinner for friends, playing board games, and staying up too late laughing.",
            "Farmers market shopping, cooking something new, and having friends over for dinner."
        ];

        return $weekends[array_rand($weekends)];
    }

    private function generateSurprisingFact()
    {
        $facts = [
            "I can solve a Rubik's cube in under 2 minutes!",
            "I've visited 15 countries but still haven't been to Scotland.",
            "I once accidentally ended up in a flash mob in Piccadilly Circus.",
            "I can play three musical instruments but am too shy to perform in public.",
            "I've never broken a bone despite being quite clumsy.",
            "I speak four languages but still can't roll my R's properly.",
            "I've run three marathons but hate running on treadmills.",
            "I can cook a perfect soufflé but burn toast regularly.",
            "I've been skydiving but am afraid of roller coasters.",
            "I memorized all the tube stations in London during lockdown out of boredom."
        ];

        return $facts[array_rand($facts)];
    }

    private function generateMessage()
    {
        $messages = [
            "Hey! How's your day going? 😊",
            "I noticed you're into hiking too! What's your favorite trail around here?",
            "Your photos from Italy look amazing! Tell me about your favorite memory from that trip",
            "Coffee lover here too ☕ What's your go-to order?",
            "I see we both love cooking! What's the last thing you made?",
            "Thanks for the super like! You seem really interesting 😄",
            "Your bio made me laugh! Do you really speak three languages?",
            "I love your taste in music! Have you heard the new album by...",
            "How was your weekend? Did you get up to anything fun?",
            "I'm always looking for new restaurant recommendations - any favorites?",
            "Your dog is adorable! What's their name?",
            "I noticed you're a teacher - what do you teach?",
            "Travel buddy needed for my next adventure - interested? 😉",
            "Hope you're having a great week!",
            "What's keeping you busy these days?"
        ];

        return $messages[array_rand($messages)];
    }

    private function generatePhoneNumber()
    {
        return '07' . rand(100000000, 999999999);
    }

    private function getRandomInterests()
    {
        $numInterests = rand(5, 12);
        $selectedInterests = array_rand(array_flip($this->interests), $numInterests);
        return is_array($selectedInterests) ? $selectedInterests : [$selectedInterests];
    }

    private function getSexualOrientation($gender)
    {
        $weights = [
            'male' => ['straight' => 85, 'gay' => 10, 'bisexual' => 5],
            'female' => ['straight' => 80, 'lesbian' => 8, 'bisexual' => 12],
            'non-binary' => ['pansexual' => 40, 'bisexual' => 30, 'straight' => 15, 'gay' => 10, 'lesbian' => 5]
        ];

        $genderWeights = $weights[$gender] ?? ['straight' => 70, 'bisexual' => 20, 'pansexual' => 10];

        $rand = rand(1, 100);
        $cumulative = 0;

        foreach ($genderWeights as $orientation => $weight) {
            $cumulative += $weight;
            if ($rand <= $cumulative) {
                return $orientation;
            }
        }

        return 'straight';
    }

    private function getPreferredGenders($gender, $sexualOrientation)
    {
        switch ($sexualOrientation) {
            case 'straight':
                return $gender === 'male' ? ['female'] : ['male'];
            case 'gay':
                return [$gender];
            case 'lesbian':
                return ['female'];
            case 'bisexual':
                return ['male', 'female'];
            case 'pansexual':
                return ['male', 'female', 'non-binary'];
            default:
                return ['male', 'female'];
        }
    }

    private function getInterestedGenders($gender)
    {
        $orientations = ['straight', 'gay', 'bisexual'];
        $orientation = $orientations[array_rand($orientations)];

        switch ($orientation) {
            case 'straight':
                return $gender === 'male' ? ['female'] : ['male'];
            case 'gay':
                return [$gender];
            case 'bisexual':
                return ['male', 'female'];
            default:
                return ['male', 'female'];
        }
    }

    private function getPreferredAgeRange()
    {
        $minAge = rand(18, 35);
        $maxAge = rand($minAge + 5, 65);
        return [$minAge, $maxAge];
    }

    private function getRandomLastActive()
    {
        $hoursAgo = rand(0, 168); // 0-168 hours (1 week)
        return Carbon::now()->subHours($hoursAgo);
    }

    private function getRandomRegistrationDate()
    {
        $daysAgo = rand(1, 365); // 1 day to 1 year ago
        return Carbon::now()->subDays($daysAgo);
    }
}
