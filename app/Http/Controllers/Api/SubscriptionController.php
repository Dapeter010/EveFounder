<?php

namespace App\Http\Controllers\Api;

//use App\Http\Controllers\Controller;
use App\Models\UserProfile;
use App\Models\StripeCustomer;
use App\Models\StripeSubscription;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class SubscriptionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        // In real app, get user from token
        $user = UserProfile::where('username', 'demo')->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        // Get user's Stripe customer
        $stripeCustomer = StripeCustomer::where('user_id', $user->user_id)->first();

        if (!$stripeCustomer) {
            return response()->json([
                'success' => true,
                'data' => null
            ]);
        }

        // Get active subscription
        $subscription = StripeSubscription::where('customer_id', $stripeCustomer->customer_id)
            ->whereNull('deleted_at')
            ->first();

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
                'customer_id' => $subscription->customer_id,
                'subscription_id' => $subscription->subscription_id,
                'price_id' => $subscription->price_id,
                'status' => $subscription->status,
                'current_period_start' => $subscription->current_period_start,
                'current_period_end' => $subscription->current_period_end,
                'cancel_at_period_end' => $subscription->cancel_at_period_end,
                'payment_method_brand' => $subscription->payment_method_brand,
                'payment_method_last4' => $subscription->payment_method_last4,
                'created_at' => $subscription->created_at,
                'updated_at' => $subscription->updated_at,
            ]
        ]);
    }

    public function cancel(Request $request): JsonResponse
    {
        // In real app, get user from token
        $user = UserProfile::where('username', 'demo')->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        // Get user's Stripe customer
        $stripeCustomer = StripeCustomer::where('user_id', $user->user_id)->first();

        if (!$stripeCustomer) {
            return response()->json([
                'success' => false,
                'message' => 'No subscription found'
            ], 404);
        }

        // Get active subscription
        $subscription = StripeSubscription::where('customer_id', $stripeCustomer->customer_id)
            ->where('status', 'active')
            ->whereNull('deleted_at')
            ->first();

        if (!$subscription) {
            return response()->json([
                'success' => false,
                'message' => 'No active subscription found'
            ], 404);
        }

        // In real app, cancel via Stripe API
        $subscription->update([
            'cancel_at_period_end' => true,
            'status' => 'canceled'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Subscription cancelled successfully',
            'data' => $subscription->fresh()
        ]);
    }
}
