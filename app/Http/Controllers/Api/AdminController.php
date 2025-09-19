<?php

namespace App\Http\Controllers\Api;

//use App\Http\Controllers;
use App\Models\UserProfile;
use App\Models\Matcher;
use App\Models\Message;
use App\Models\ProfileBoost;
use App\Models\Like;
use App\Models\Report;
use App\Models\ContentModeration;
use App\Models\PlatformSetting;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    public function __construct()
    {
        // In real app, add auth middleware and admin role check
    }

    public function dashboard(): JsonResponse
    {
        $stats = [
            'total_users' => UserProfile::count(),
            'active_users' => UserProfile::where('last_active_at', '>=', now()->subDays(30))->count(),
            'new_users_today' => UserProfile::whereDate('created_at', today())->count(),
            'new_users_this_week' => UserProfile::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'new_users_this_month' => UserProfile::whereMonth('created_at', now()->month)->count(),

            'total_matches' => DB::table('matches')->count(),
            'matches_today' => DB::table('matches')->whereDate('matched_at', today())->count(),
            'matches_this_week' => DB::table('matches')->whereBetween('matched_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),

            'total_messages' => DB::table('messages')->count(),
            'messages_today' => DB::table('messages')->whereDate('created_at', today())->count(),

            'total_subscriptions' => DB::table('stripe_subscriptions')->where('status', 'active')->count(),
            'subscription_revenue_monthly' => 64940, // Mock data

            'total_boosts' => ProfileBoost::count(),
            'boost_revenue_monthly' => ProfileBoost::whereMonth('created_at', now()->month)->sum('cost'),

            'total_likes' => Like::count(),
            'likes_today' => Like::whereDate('created_at', today())->count(),

            'total_reports' => Report::count(),
            'pending_reports' => Report::where('status', 'pending')->count(),
            'verified_users' => UserProfile::where('is_verified', true)->count(),
            'premium_users' => DB::table('stripe_subscriptions')->where('status', 'active')->where('price_id', 'like', '%premium%')->count(),
        ];

        // User growth chart data (last 30 days)
        $userGrowth = UserProfile::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Revenue chart data (last 12 months)
        $revenueData = []; // Mock data - would come from Stripe

        return response()->json([
            'success' => true,
            'data' => [
                'stats' => $stats,
                'user_growth' => $userGrowth,
                'revenue_data' => $revenueData,
            ]
        ]);
    }

    public function users(Request $request): JsonResponse
    {
        $query = UserProfile::query();

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        // Filter by subscription
        if ($request->has('subscription')) {
            // In real app, join with subscription data
        }

        $users = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }

    public function userDetails($userId): JsonResponse
    {
        $user = UserProfile::where('user_id', $userId)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        // Get user stats
        $stats = [
            'total_likes_sent' => Like::where('liker_id', $user->id)->count(),
            'total_likes_received' => Like::where('liked_id', $user->id)->count(),
            'total_matches' => DB::table('matches')
                ->where(function ($query) use ($user) {
                    $query->where('user1_id', $user->id)
                          ->orWhere('user2_id', $user->id);
                })
                ->count(),
            'total_messages_sent' => DB::table('messages')->where('sender_id', $user->id)->count(),
            'total_messages_received' => DB::table('messages')->where('receiver_id', $user->id)->count(),
            'profile_views' => ProfileView::where('viewed_id', $user->id)->count(),
            'total_spent' => ProfileBoost::where('user_id', $user->id)->sum('cost'),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'user' => $user,
                'stats' => $stats
            ]
        ]);
    }

    public function updateUser(Request $request, $userId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'is_active' => 'boolean',
            'is_verified' => 'boolean',
            'admin_notes' => 'nullable|string|max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => implode(",", $validator->errors()->all()),
            ], 422);
        }

        $user = UserProfile::where('user_id', $userId)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        $user->update($request->only(['is_active', 'is_verified', 'admin_notes']));

        return response()->json([
            'success' => true,
            'message' => 'User updated successfully',
            'data' => $user->fresh()
        ]);
    }

    public function reports(Request $request): JsonResponse
    {
        $query = DB::table('reports')
            ->join('user_profiles as reporter', 'reports.reporter_id', '=', 'reporter.user_id')
            ->join('user_profiles as reported', 'reports.reported_id', '=', 'reported.user_id')
            ->select([
                'reports.*',
                'reporter.first_name as reporter_name',
                'reporter.username as reporter_username',
                'reported.first_name as reported_name',
                'reported.username as reported_username'
            ]);

        if ($request->has('status')) {
            $query->where('reports.status', $request->status);
        }

        if ($request->has('type')) {
            $query->where('reports.type', $request->type);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('reporter.first_name', 'like', "%{$search}%")
                  ->orWhere('reported.first_name', 'like', "%{$search}%")
                  ->orWhere('reports.reason', 'like', "%{$search}%");
            });
        }

        $reports = $query->orderBy('reports.created_at', 'desc')
            ->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $reports
        ]);
    }

    public function updateReport(Request $request, $reportId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,investigating,resolved,dismissed',
            'admin_notes' => 'nullable|string|max:1000',
            'handled_by' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => implode(",", $validator->errors()->all()),
            ], 422);
        }

        $report = Report::find($reportId);

        if (!$report) {
            return response()->json([
                'success' => false,
                'message' => 'Report not found'
            ], 404);
        }

        $updateData = $request->only(['status', 'admin_notes', 'handled_by']);

        if ($request->status === 'resolved') {
            $updateData['resolved_at'] = now();
        }

        $report->update($updateData);

        return response()->json([
            'success' => true,
            'message' => 'Report updated successfully',
            'data' => $report->fresh()
        ]);
    }

    public function subscriptions(Request $request): JsonResponse
    {
        // Mock subscription data - in real app, get from Stripe/Supabase
        $subscriptions = [
            [
                'id' => 1,
                'user' => ['name' => 'Emma Thompson', 'email' => 'emma@example.com', 'id' => 123],
                'plan' => 'Premium',
                'amount' => '£19.99',
                'status' => 'active',
                'next_billing' => '2025-02-15',
                'created_at' => '2025-01-15',
                'stripe_id' => 'sub_1234567890',
                'payment_method' => '•••• 4242',
                'total_paid' => '£59.97',
                'billing_cycles' => 3
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'current_page' => 1,
                'data' => $subscriptions,
                'total' => count($subscriptions),
            ]
        ]);
    }

    public function analytics(Request $request): JsonResponse
    {
        $period = $request->get('period', '30'); // days

        // User engagement metrics
        $engagement = [
            'daily_active_users' => UserProfile::where('last_active_at', '>=', now()->subDay())->count(),
            'weekly_active_users' => UserProfile::where('last_active_at', '>=', now()->subWeek())->count(),
            'monthly_active_users' => UserProfile::where('last_active_at', '>=', now()->subMonth())->count(),
            'average_session_duration' => 25, // This would come from analytics tracking
            'bounce_rate' => 0.15,
        ];

        // Matching metrics
        $matching = [
            'total_likes' => Like::where('created_at', '>=', now()->subDays($period))->count(),
            'total_matches' => DB::table('matches')->where('matched_at', '>=', now()->subDays($period))->count(),
            'match_rate' => 0.23, // Calculated from likes to matches ratio
            'average_matches_per_user' => 3.2,
        ];

        // Revenue metrics
        $revenue = [
            'total_revenue' => 83410, // Mock data
            'subscription_revenue' => 64940,
            'boost_revenue' => ProfileBoost::where('created_at', '>=', now()->subDays($period))->sum('cost'),
            'average_revenue_per_user' => 12.50,
            'churn_rate' => 0.05,
        ];

        // Geographic data
        $geographic = UserProfile::selectRaw('location, COUNT(*) as count')
            ->groupBy('location')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'engagement' => $engagement,
                'matching' => $matching,
                'revenue' => $revenue,
                'geographic' => $geographic,
            ]
        ]);
    }

    public function getContent(Request $request): JsonResponse
    {
        $type = $request->get('type', 'photos');
        $status = $request->get('status', 'pending');

        $content = ContentModeration::where('content_type', $type)
            ->where('status', $status)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'user' => $item->user->first_name . ' ' . $item->user->last_name,
                    'userId' => $item->user_id,
                    'photoUrl' => $item->content_url,
                    'bio' => $item->content_text,
                    'uploadedAt' => $item->created_at->format('Y-m-d H:i'),
                    'status' => $item->status,
                    'aiScore' => $item->ai_score,
                    'flags' => $item->ai_flags ?? [],
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $content
        ]);
    }

    public function updateContent(Request $request, $type, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:approved,rejected',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => implode(",", $validator->errors()->all()),
            ], 422);
        }

        $content = ContentModeration::where('content_type', $type)->find($id);

        if (!$content) {
            return response()->json([
                'success' => false,
                'message' => 'Content not found'
            ], 404);
        }

        $content->update([
            'status' => $request->status,
            'reviewed_by' => 'admin-user-id', // In real app, get from token
            'reviewed_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Content status updated successfully',
            'data' => $content->fresh()
        ]);
    }

    public function getSettings(): JsonResponse
    {
        $settings = [
            'platform' => [
                'minAge' => PlatformSetting::get('platform.minAge', 18),
                'maxAge' => PlatformSetting::get('platform.maxAge', 65),
                'maxDistance' => PlatformSetting::get('platform.maxDistance', 100),
                'dailySuperLikes' => PlatformSetting::get('platform.dailySuperLikes', 5),
                'maxPhotos' => PlatformSetting::get('platform.maxPhotos', 6),
                'bioMaxLength' => PlatformSetting::get('platform.bioMaxLength', 500),
                'maintenanceMode' => PlatformSetting::get('platform.maintenanceMode', false),
                'newRegistrations' => PlatformSetting::get('platform.newRegistrations', true),
            ],
            'matching' => [
                'aiMatching' => PlatformSetting::get('matching.aiMatching', true),
                'compatibilityThreshold' => PlatformSetting::get('matching.compatibilityThreshold', 60),
                'boostDuration' => PlatformSetting::get('matching.boostDuration', 30),
                'matchExpiry' => PlatformSetting::get('matching.matchExpiry', 30),
                'autoHideInactive' => PlatformSetting::get('matching.autoHideInactive', true),
                'inactiveDays' => PlatformSetting::get('matching.inactiveDays', 30),
            ],
            'safety' => [
                'photoVerification' => PlatformSetting::get('safety.photoVerification', true),
                'autoModeration' => PlatformSetting::get('safety.autoModeration', true),
                'reportThreshold' => PlatformSetting::get('safety.reportThreshold', 3),
                'autoSuspend' => PlatformSetting::get('safety.autoSuspend', true),
                'requirePhoneVerification' => PlatformSetting::get('safety.requirePhoneVerification', false),
                'allowVideoChat' => PlatformSetting::get('safety.allowVideoChat', true),
            ],
            'notifications' => [
                'systemNotifications' => true,
                'emailNotifications' => true,
                'pushNotifications' => true,
                'marketingEmails' => false,
                'weeklyDigest' => true,
                'adminAlerts' => true,
            ],
            'billing' => [
                'basicPrice' => PlatformSetting::get('billing.basicPrice', 9.99),
                'premiumPrice' => PlatformSetting::get('billing.premiumPrice', 19.99),
                'boostPrice' => PlatformSetting::get('billing.boostPrice', 4.99),
                'superBoostPrice' => PlatformSetting::get('billing.superBoostPrice', 9.99),
                'weekendBoostPrice' => PlatformSetting::get('billing.weekendBoostPrice', 14.99),
                'currency' => PlatformSetting::get('billing.currency', 'GBP'),
                'taxRate' => PlatformSetting::get('billing.taxRate', 20),
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $settings
        ]);
    }

    public function updateSettings(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'platform' => 'nullable|array',
            'matching' => 'nullable|array',
            'safety' => 'nullable|array',
            'notifications' => 'nullable|array',
            'billing' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => implode(",", $validator->errors()->all()),
            ], 422);
        }

        // Update platform settings
        foreach ($request->all() as $section => $settings) {
            if (is_array($settings)) {
                foreach ($settings as $key => $value) {
                    $settingKey = $section . '.' . $key;
                    $type = is_bool($value) ? 'boolean' : (is_numeric($value) ? (is_float($value) ? 'float' : 'integer') : 'string');
                    PlatformSetting::set($settingKey, $value, $type);
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Settings updated successfully'
        ]);
    }

    public function sendNotification(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_ids' => 'required|array',
            'user_ids.*' => 'string',
            'title' => 'required|string|max:255',
            'content' => 'required|string|max:1000',
            'type' => 'required|in:announcement,promotion,warning,info'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => implode(",", $validator->errors()->all()),
            ], 422);
        }

        // In real app, send notifications via Supabase or notification service
        $sentCount = count($request->user_ids);

        return response()->json([
            'success' => true,
            'message' => 'Notifications sent successfully',
            'data' => ['sent_count' => $sentCount]
        ]);
    }

    public function exportUsers(Request $request): JsonResponse
    {
        $users = UserProfile::query()
            ->select([
                'user_id', 'first_name', 'last_name', 'username', 'date_of_birth',
                'gender', 'location', 'is_active', 'is_verified', 'created_at'
            ])
            ->get()
            ->map(function ($user) {
                return [
                    'ID' => $user->id,
                    'Name' => $user->first_name . ' ' . $user->last_name,
                    'Username' => $user->username,
                    'Age' => $user->date_of_birth->age,
                    'Gender' => $user->gender,
                    'Location' => $user->location,
                    'Status' => $user->is_active ? 'Active' : 'Inactive',
                    'Verified' => $user->is_verified ? 'Yes' : 'No',
                    'Subscription' => 'None', // In real app, join with subscription data
                    'Joined' => $user->created_at->format('Y-m-d'),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $users,
            'filename' => 'evefound_users_' . now()->format('Y-m-d') . '.csv'
        ]);
    }
}
