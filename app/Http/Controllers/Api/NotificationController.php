<?php

namespace App\Http\Controllers\Api;

use App\Models\PushSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

class NotificationController extends Controller
{
    /**
     * Subscribe to push notifications
     */
    public function subscribe(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'endpoint' => 'required|string|max:500',
            'keys.p256dh' => 'required|string',
            'keys.auth' => 'required|string',
            'device_name' => 'nullable|string|max:255',
            'browser' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();

        // Check if subscription already exists
        $subscription = PushSubscription::where('user_id', $user->id)
            ->where('endpoint', $request->endpoint)
            ->first();

        if ($subscription) {
            // Update existing subscription
            $subscription->update([
                'p256dh_key' => $request->input('keys.p256dh'),
                'auth_token' => $request->input('keys.auth'),
                'device_name' => $request->device_name,
                'browser' => $request->browser,
                'last_used_at' => now(),
            ]);
        } else {
            // Create new subscription
            $subscription = PushSubscription::create([
                'user_id' => $user->id,
                'endpoint' => $request->endpoint,
                'p256dh_key' => $request->input('keys.p256dh'),
                'auth_token' => $request->input('keys.auth'),
                'device_name' => $request->device_name,
                'browser' => $request->browser,
                'last_used_at' => now(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Push notification subscription registered successfully',
            'data' => [
                'subscription_id' => $subscription->id,
            ]
        ]);
    }

    /**
     * Unsubscribe from push notifications
     */
    public function unsubscribe(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'endpoint' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();

        $deleted = PushSubscription::where('user_id', $user->id)
            ->where('endpoint', $request->endpoint)
            ->delete();

        if ($deleted) {
            return response()->json([
                'success' => true,
                'message' => 'Push notification subscription removed successfully',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Subscription not found',
        ], 404);
    }

    /**
     * Get all active push subscriptions for the authenticated user
     */
    public function getSubscriptions(Request $request): JsonResponse
    {
        $user = $request->user();

        $subscriptions = PushSubscription::where('user_id', $user->id)
            ->orderBy('last_used_at', 'desc')
            ->get()
            ->map(function ($sub) {
                return [
                    'id' => $sub->id,
                    'device_name' => $sub->device_name,
                    'browser' => $sub->browser,
                    'last_used_at' => $sub->last_used_at?->toIso8601String(),
                    'created_at' => $sub->created_at->toIso8601String(),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $subscriptions,
        ]);
    }

    /**
     * Send a test notification
     */
    public function sendTestNotification(Request $request): JsonResponse
    {
        $user = $request->user();

        $subscriptions = PushSubscription::where('user_id', $user->id)->get();

        if ($subscriptions->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No push subscriptions found. Please enable notifications first.',
            ], 404);
        }

        $vapidPublicKey = env('VAPID_PUBLIC_KEY');
        $vapidPrivateKey = env('VAPID_PRIVATE_KEY');

        if (!$vapidPublicKey || !$vapidPrivateKey) {
            return response()->json([
                'success' => false,
                'message' => 'Push notification keys not configured on server',
            ], 500);
        }

        $auth = [
            'VAPID' => [
                'subject' => env('APP_URL', 'https://evefound.com'),
                'publicKey' => $vapidPublicKey,
                'privateKey' => $vapidPrivateKey,
            ],
        ];

        $webPush = new WebPush($auth);

        $payload = json_encode([
            'title' => 'Test Notification',
            'body' => 'Your notifications are working! 🎉',
            'icon' => '/icon-192x192.png',
            'badge' => '/badge-72x72.png',
            'tag' => 'test-notification',
            'data' => [
                'url' => '/dashboard',
            ],
        ]);

        $sentCount = 0;
        $failedCount = 0;

        foreach ($subscriptions as $sub) {
            $subscription = Subscription::create([
                'endpoint' => $sub->endpoint,
                'publicKey' => $sub->p256dh_key,
                'authToken' => $sub->auth_token,
            ]);

            try {
                $report = $webPush->sendOneNotification($subscription, $payload);

                if ($report->isSuccess()) {
                    $sentCount++;
                } else {
                    $failedCount++;
                    // If subscription is expired or invalid, delete it
                    if ($report->isSubscriptionExpired()) {
                        $sub->delete();
                    }
                }
            } catch (\Exception $e) {
                $failedCount++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Test notification sent to {$sentCount} device(s)",
            'data' => [
                'sent' => $sentCount,
                'failed' => $failedCount,
            ],
        ]);
    }
}
