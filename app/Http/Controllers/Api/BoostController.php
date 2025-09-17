<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\ProfileBoost;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class BoostController extends Controller
{
    /**
     * Get boost configuration options
     */
    private function getBoostOptions(): array
    {
        return [
            'profile' => [
                'name' => 'Profile Boost',
                'price' => 4.99,
                'currency' => 'gbp',
                'duration' => 30, // minutes
                'stripe_price_id' => env('STRIPE_PROFILE_BOOST_PRICE_ID')
            ],
            'super' => [
                'name' => 'Super Boost',
                'price' => 9.99,
                'currency' => 'gbp',
                'duration' => 180, // minutes
                'stripe_price_id' => env('STRIPE_SUPER_BOOST_PRICE_ID')
            ],
            'weekend' => [
                'name' => 'Weekend Boost',
                'price' => 14.99,
                'currency' => 'gbp',
                'duration' => 2880, // minutes
                'stripe_price_id' => env('STRIPE_WEEKEND_BOOST_PRICE_ID')
            ]
        ];
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated'
            ], 401);
        }

        $boostOptions = [
            [
                'id' => 'profile',
                'name' => 'Profile Boost',
                'icon' => 'TrendingUp',
                'price' => '£4.99',
                'duration' => '30 minutes',
                'description' => 'Be one of the top profiles in your area for 30 minutes',
                'benefits' => [
                    'Up to 10x more profile views',
                    'Priority placement in discovery',
                    'Increased match potential',
                    'Real-time boost analytics'
                ],
                'color' => 'from-blue-500 to-cyan-500',
                'popular' => false
            ],
            [
                'id' => 'super',
                'name' => 'Super Boost',
                'icon' => 'Zap',
                'price' => '£9.99',
                'duration' => '3 hours',
                'description' => 'Maximum visibility for 3 hours with premium features',
                'benefits' => [
                    'Up to 25x more profile views',
                    'Top position in all discovery feeds',
                    'Free Super Likes included',
                    'Priority customer support',
                    'Detailed analytics report'
                ],
                'color' => 'from-yellow-500 to-orange-600',
                'popular' => true
            ],
            [
                'id' => 'weekend',
                'name' => 'Weekend Boost',
                'icon' => 'Star',
                'price' => '£14.99',
                'duration' => 'Full weekend',
                'description' => 'Premium visibility throughout the entire weekend',
                'benefits' => [
                    'Featured profile all weekend',
                    'Unlimited Super Likes',
                    'Priority in all matches',
                    'Weekend activity report',
                    'Money-back guarantee'
                ],
                'color' => 'from-pink-500 to-purple-600',
                'popular' => false
            ]
        ];

        return response()->json([
            'success' => true,
            'data' => $boostOptions
        ]);
    }

    /**
     * Creates a Stripe checkout session for boost purchase
     * Called when user clicks "Purchase Boost" button
     */
    public function createCheckoutSession(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'boost_id' => 'required|in:profile,super,weekend',
            'success_url' => 'required|url',
            'cancel_url' => 'required|url'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated'
            ], 401);
        }

        // Check if user has an active boost
        $activeBoost = ProfileBoost::where('user_id', $user->id)
            ->where('status', 'active')
            ->where('ends_at', '>', now())
            ->first();

        if ($activeBoost) {
            return response()->json([
                'success' => false,
                'message' => 'You already have an active boost. Please wait until it expires before purchasing another.'
            ], 400);
        }
        Log::info("got to A");
        $boostId = $request->boost_id;
        $boostOptions = $this->getBoostOptions();
        $boostConfig = $boostOptions[$boostId];

        if (!$boostConfig['stripe_price_id']) {
            return response()->json([
                'success' => false,
                'message' => 'Stripe price ID not configured for this boost type'
            ], 500);
        }

        // Create pending boost record (will be activated after payment)
        $pendingBoost = ProfileBoost::create([
            'user_id' => $user->id,
            'boost_type' => $boostId,
            'cost' => $boostConfig['price'],
            'starts_at' => null,
            'ends_at' => null,
            'views_gained' => 0,
            'likes_gained' => 0,
            'matches_gained' => 0,
            'status' => 'pending',
        ]);

        // Call Supabase Edge Function to create Stripe checkout session
        try {
            Log::info("got to B");

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . env('SUPABASE_ANON_KEY'),
                'Content-Type' => 'application/json',
            ])->post(env('SUPABASE_URL') . '/functions/v1/stripe-checkout', [
                'boost_id' => $boostId,
                'price_id' => $boostConfig['stripe_price_id'],
                'success_url' => $request->success_url . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => $request->cancel_url,
                'mode' => 'payment', // One-time payment for boosts
                'metadata' => [
                    'user_id' => $user->id,
                    'boost_id' => $boostId,
                    'pending_boost_id' => $pendingBoost->id,
                    'type' => 'boost_purchase'
                ],
            ]);
            Log::info("got to C");

            if ($response->successful()) {
                $data = $response->json();

                // Store Stripe session ID for tracking
                $pendingBoost->update([
                    'stripe_session_id' => $data['sessionId']
                ]);

                return response()->json([
                    'success' => true,
                    'data' => [
                        'session_id' => $data['sessionId'],
                        'url' => $data['url'],
                        'pending_boost_id' => $pendingBoost->id
                    ]
                ]);
            } else {
                // Clean up pending boost if Stripe session creation failed
                $pendingBoost->delete();

                Log::error('Stripe checkout session creation failed', [
                    'response' => $response->body(),
                    'boost_id' => $boostId,
                    'user_id' => $user->id
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create checkout session'
                ], 500);
            }
        } catch (\Exception $e) {
            // Clean up pending boost on exception
            $pendingBoost->delete();

            Log::error('Exception creating Stripe checkout session', [
                'error' => $e->getMessage(),
                'boost_id' => $boostId,
                'user_id' => $user->id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error creating checkout session: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Checks if payment was completed and boost was activated
     * Called by success page to verify boost activation
     */
    public function checkPaymentStatus(Request $request, $sessionId): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated'
            ], 401);
        }

        // Look for boost with this session ID that was recently activated
        $recentBoost = ProfileBoost::where('user_id', $user->id)
            ->where('stripe_session_id', $sessionId)
            ->where('status', 'active')
            ->where('starts_at', '>', now()->subMinutes(10)) // Activated in last 10 minutes
            ->orderBy('starts_at', 'desc')
            ->first();

        if ($recentBoost) {
            $remainingMinutes = now()->diffInMinutes($recentBoost->ends_at, false);

            return response()->json([
                'success' => true,
                'data' => [
                    'boost_activated' => true,
                    'boost' => [
                        'id' => $recentBoost->id,
                        'type' => $recentBoost->boost_type,
                        'name' => ucfirst($recentBoost->boost_type) . ' Boost',
                        'ends_at' => $recentBoost->ends_at->format('Y-m-d H:i:s'),
                        'remaining_minutes' => max(0, $remainingMinutes)
                    ]
                ]
            ]);
        }

        // Check if there's a pending boost with this session (payment might still be processing)
        $pendingBoost = ProfileBoost::where('user_id', $user->id)
            ->where('stripe_session_id', $sessionId)
            ->where('status', 'pending')
            ->first();

        if ($pendingBoost) {
            return response()->json([
                'success' => true,
                'data' => [
                    'boost_activated' => false,
                    'payment_processing' => true,
                    'message' => 'Payment is being processed. Your boost will activate shortly.'
                ]
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'boost_activated' => false,
                'payment_processing' => false,
                'message' => 'No boost found for this payment session.'
            ]
        ]);
    }

    /**
     * Handles webhooks from Stripe (via Supabase Edge Function)
     * Activates boost when payment is confirmed
     */
    public function handleWebhook(Request $request): JsonResponse
    {
        // Enhanced validation
        $validator = Validator::make($request->all(), [
            'type' => 'required|string|in:checkout.session.completed,payment_intent.succeeded',
            'data' => 'required|array',
            'data.session_id' => 'required_if:type,checkout.session.completed|string',
            'data.payment_intent_id' => 'nullable|string',
            'data.customer_id' => 'required|string',
            'data.metadata' => 'required|array',
            'data.metadata.user_id' => 'required|uuid',
            'data.metadata.boost_id' => 'required|string|in:profile,super,weekend',
            'data.metadata.type' => 'required|string|in:boost_purchase',
        ]);

        if ($validator->fails()) {
            Log::error('Invalid webhook data received', [
                'errors' => $validator->errors(),
                'data' => $request->all(),
                'ip' => $request->ip()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Invalid webhook data',
                'errors' => $validator->errors()
            ], 422);
        }

        $webhookId = uniqid('webhook_');

        Log::info('Boost webhook received', [
            'webhook_id' => $webhookId,
            'type' => $request->type,
            'session_id' => $request->input('data.session_id'),
            'user_id' => $request->input('data.metadata.user_id'),
            'boost_id' => $request->input('data.metadata.boost_id'),
            'ip' => $request->ip()
        ]);
        try {
            if ($request->type === 'checkout.session.completed') {
                $sessionData = $request->data;
                $metadata = $sessionData['metadata'] ?? [];

                if (($metadata['type'] ?? '') === 'boost_purchase') {
                    $result = $this->activateBoostFromPayment($metadata, $sessionData);

                    if (!$result['success']) {
                        Log::error('Failed to activate boost', [
                            'webhook_id' => $webhookId,
                            'error' => $result['message'],
                            'session_id' => $sessionData['session_id'] ?? null
                        ]);

                        return response()->json([
                            'success' => false,
                            'message' => $result['message']
                        ], 500);
                    }

                    Log::info('Boost activated successfully', [
                        'webhook_id' => $webhookId,
                        'boost_id' => $result['boost_id'],
                        'session_id' => $sessionData['session_id'] ?? null
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'webhook_id' => $webhookId
            ]);

        } catch (\Exception $e) {
            Log::error('Exception processing webhook', [
                'webhook_id' => $webhookId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Internal server error'
            ], 500);
        }
    }

    /**
     * Private method to activate boost after successful payment
     */
    private function activateBoostFromPayment(array $metadata, array $sessionData): array
    {
        $userId = $metadata['user_id'] ?? null;
        $boostId = $metadata['boost_id'] ?? null;
        $sessionId = $sessionData['session_id'] ?? null;

        if (!$userId || !$boostId || !$sessionId) {
            return [
                'success' => false,
                'message' => 'Missing required metadata',
                'missing' => array_filter([
                    'user_id' => !$userId,
                    'boost_id' => !$boostId,
                    'session_id' => !$sessionId
                ])
            ];
        }

        // Verify user exists
        $user = User::find($userId);
        if (!$user) {
            return [
                'success' => false,
                'message' => 'User not found',
                'user_id' => $userId
            ];
        }

        // Check for existing active boost
        $existingBoost = ProfileBoost::where('user_id', $userId)
            ->where('status', 'active')
            ->where('ends_at', '>', now())
            ->first();

        if ($existingBoost) {
            return [
                'success' => false,
                'message' => 'User already has an active boost',
                'existing_boost_id' => $existingBoost->id
            ];
        }

        // Check if this session was already processed
        $existingProcessedBoost = ProfileBoost::where('stripe_session_id', $sessionId)
            ->where('status', 'active')
            ->first();

        if ($existingProcessedBoost) {
            return [
                'success' => true,
                'message' => 'Boost already processed',
                'boost_id' => $existingProcessedBoost->id
            ];
        }

        $boostConfig = $this->getBoostOptions()[$boostId];
        $duration = $boostConfig['duration'];

        $startsAt = now();
        $endsAt = $startsAt->copy()->addMinutes($duration);

        // Create or update boost record
        $boost = ProfileBoost::updateOrCreate(
            ['stripe_session_id' => $sessionId],
            [
                'user_id' => $userId,
                'boost_type' => $boostId,
                'cost' => $boostConfig['price'],
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'status' => 'active',
                'stripe_payment_intent_id' => $sessionData['payment_intent_id'] ?? null,
                'payment_completed_at' => now(),
                'views_gained' => 0,
                'likes_gained' => 0,
                'matches_gained' => 0,
            ]
        );

        return [
            'success' => true,
            'message' => 'Boost activated successfully',
            'boost_id' => $boost->id
        ];
    }

    public function history(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated'
            ], 401);
        }

        $boosts = ProfileBoost::where('user_id', $user->id)
            ->whereIn('status', ['active', 'completed']) // Exclude pending and cancelled
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($boost) {
                return [
                    'id' => $boost->id,
                    'date' => $boost->created_at->format('Y-m-d'),
                    'type' => ucfirst($boost->boost_type) . ' Boost',
                    'views' => $boost->views_gained ?? 0,
                    'likes' => $boost->likes_gained ?? 0,
                    'matches' => $boost->matches_gained ?? 0,
                    'cost' => '£' . number_format($boost->cost, 2),
                    'duration' => $this->getBoostDurationText($boost->boost_type),
                    'status' => $boost->status,
                    'started_at' => $boost->starts_at ? $boost->starts_at->format('Y-m-d H:i') : null,
                    'ended_at' => $boost->ends_at ? $boost->ends_at->format('Y-m-d H:i') : null,
                    'is_active' => $boost->status === 'active' && $boost->ends_at > now()
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $boosts
        ]);
    }

    public function current(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated'
            ], 401);
        }

        $activeBoost = ProfileBoost::where('user_id', $user->id)
            ->where('status', 'active')
            ->where('ends_at', '>', now())
            ->orderBy('starts_at', 'desc')
            ->first();

        if (!$activeBoost) {
            return response()->json([
                'success' => true,
                'data' => null,
                'message' => 'No active boost'
            ]);
        }

        $remainingMinutes = now()->diffInMinutes($activeBoost->ends_at, false);
        $remainingMinutes = max(0, $remainingMinutes);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $activeBoost->id,
                'boost_type' => $activeBoost->boost_type,
                'type' => ucfirst($activeBoost->boost_type) . ' Boost',
                'started_at' => $activeBoost->starts_at->format('Y-m-d H:i'),
                'ends_at' => $activeBoost->ends_at->format('Y-m-d H:i'),
                'remaining_minutes' => $remainingMinutes,
                'views_gained' => $activeBoost->views_gained ?? 0,
                'likes_gained' => $activeBoost->likes_gained ?? 0,
                'matches_gained' => $activeBoost->matches_gained ?? 0,
                'cost' => '£' . number_format($activeBoost->cost, 2)
            ]
        ]);
    }

    private function getBoostDurationText($boostType): string
    {
        return match ($boostType) {
            'profile' => '30 minutes',
            'super' => '3 hours',
            'weekend' => 'Full weekend',
            default => 'Unknown'
        };
    }

    /**
     * This would typically be called by a scheduled job to update boost statistics
     */
    public function updateStats(Request $request, $boostId): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated'
            ], 401);
        }

        $boost = ProfileBoost::where('id', $boostId)
            ->where('user_id', $user->id)
            ->first();

        if (!$boost) {
            return response()->json([
                'success' => false,
                'message' => 'Boost not found'
            ], 404);
        }

        // In a real implementation, you'd calculate actual stats
        // For now, simulate some activity during boost period
        $elapsedMinutes = now()->diffInMinutes($boost->starts_at);
        $totalMinutes = $boost->starts_at->diffInMinutes($boost->ends_at);
        $progress = min(1, $elapsedMinutes / $totalMinutes);

        // Simulate boost effectiveness based on boost type
        $multiplier = match ($boost->boost_type) {
            'profile' => ['views' => 10, 'likes' => 3, 'matches' => 1],
            'super' => ['views' => 25, 'likes' => 8, 'matches' => 3],
            'weekend' => ['views' => 15, 'likes' => 5, 'matches' => 2],
            default => ['views' => 5, 'likes' => 1, 'matches' => 0]
        };

        $estimatedViews = (int)($progress * $multiplier['views'] * 10);
        $estimatedLikes = (int)($progress * $multiplier['likes'] * 3);
        $estimatedMatches = (int)($progress * $multiplier['matches']);

        $boost->update([
            'views_gained' => $estimatedViews,
            'likes_gained' => $estimatedLikes,
            'matches_gained' => $estimatedMatches
        ]);

        // Mark as completed if boost period has ended
        if (now() >= $boost->ends_at && $boost->status === 'active') {
            $boost->update(['status' => 'completed']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Boost stats updated',
            'data' => [
                'views_gained' => $boost->views_gained,
                'likes_gained' => $boost->likes_gained,
                'matches_gained' => $boost->matches_gained,
                'status' => $boost->status
            ]
        ]);
    }
}
