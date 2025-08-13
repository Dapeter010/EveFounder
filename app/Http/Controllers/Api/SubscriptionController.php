<?php

namespace App\Http\Controllers\Api;

//use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;

class SubscriptionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        // Extract user ID from token
        $authHeader = $request->header('Authorization');
        $userId = str_replace('Bearer mock-token-', '', $authHeader);
        $user = User::find($userId);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        $subscription = $user->subscription;

        if (!$subscription) {
            return response()->json([
                'success' => true,
                'data' => null
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $subscription->id,
                'plan_type' => $subscription->plan_type,
                'status' => $subscription->status,
                'amount' => $subscription->amount,
                'currency' => $subscription->currency,
                'starts_at' => $subscription->starts_at,
                'ends_at' => $subscription->ends_at,
                'stripe_subscription_id' => $subscription->stripe_subscription_id,
                'created_at' => $subscription->created_at,
                'updated_at' => $subscription->updated_at,
            ]
        ]);
    }

    public function createCheckoutSession(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'price_id' => 'required|string',
            'success_url' => 'required|url',
            'cancel_url' => 'required|url',
            'mode' => 'required|in:subscription,payment',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Extract user ID from token
        $authHeader = $request->header('Authorization');
        $userId = str_replace('Bearer mock-token-', '', $authHeader);
        $user = User::find($userId);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        // In a real app, this would create a Stripe checkout session
        // For now, we'll simulate the process and create a subscription directly

        $planType = str_contains($request->price_id, 'premium') ? 'premium' : 'basic';
        $amount = $planType === 'premium' ? 19.99 : 9.99;

        // Create or update subscription
        $subscription = Subscription::updateOrCreate(
            ['user_id' => $user->id],
            [
                'plan_type' => $planType,
                'status' => 'active',
                'amount' => $amount,
                'currency' => 'GBP',
                'stripe_subscription_id' => 'sub_mock_' . uniqid(),
                'starts_at' => now(),
                'ends_at' => now()->addMonth(),
            ]
        );

        // Return mock checkout URL that redirects to success page
        return response()->json([
            'success' => true,
            'data' => [
                'sessionId' => 'cs_mock_' . uniqid(),
                'url' => $request->success_url
            ]
        ]);
    }

    public function cancel(Request $request): JsonResponse
    {
        // Extract user ID from token
        $authHeader = $request->header('Authorization');
        $userId = str_replace('Bearer mock-token-', '', $authHeader);
        $user = User::find($userId);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        $subscription = $user->subscription;

        if (!$subscription) {
            return response()->json([
                'success' => false,
                'message' => 'No active subscription found'
            ], 404);
        }

        $subscription->update([
            'status' => 'cancelled',
            'cancelled_at' => now()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Subscription cancelled successfully',
            'data' => $subscription->fresh()
        ]);
    }
}
