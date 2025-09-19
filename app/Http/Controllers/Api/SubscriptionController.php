<?php

namespace App\Http\Controllers\Api;

//use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class SubscriptionController extends Controller
{
    public function index(Request $request): JsonResponse
    {

        $user = Auth::user();

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

    // In SubscriptionController::createCheckoutSession()
    public function createCheckoutSession(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'price_id' => 'required|string',
            'success_url' => 'required|url',
            'cancel_url' => 'required|url',
            'mode' => 'required|in:payment,subscription'
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

        // Call Supabase Edge Function to create Stripe checkout session
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . env('SUPABASE_ANON_KEY'),
                'Content-Type' => 'application/json',
            ])->post(env('SUPABASE_URL') . '/functions/v1/stripe-checkout', [
                'price_id' => $request->price_id,
                'success_url' => $request->success_url,
                'cancel_url' => $request->cancel_url,
                'mode' => $request->mode,
                'metadata' => [
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'type' => $request->mode === 'subscription' ? 'subscription_purchase' : 'one_time_payment'
                ],
            ]);

            if ($response->successful()) {


//            // Create or update subscription
//            $subscription = Subscription::updateOrCreate(
//                ['user_id' => $user->id],
//                [
//                    'plan_type' => $planType,
//                    'status' => 'active',
//                    'amount' => $amount,
//                    'currency' => 'GBP',
//                    'stripe_subscription_id' => 'sub_mock_' . uniqid(),
//                    'starts_at' => now(),
//                    'ends_at' => now()->addMonth(),
//                ]
//            );

                $data = $response->json();
                return response()->json([
                    'success' => true,
                    'data' => [
                        'sessionId' => $data['sessionId'],
                        'url' => $data['url']
                    ]
                ]);
            } else {
                Log::error('Stripe checkout session creation failed', [
                    'response' => $response->body(),
                    'price_id' => $request->price_id,
                    'user_id' => $user->id
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create checkout session'
                ], 500);
            }
        } catch (\Exception $e) {
            Log::error('Exception creating Stripe checkout session', [
                'error' => $e->getMessage(),
                'price_id' => $request->price_id,
                'user_id' => $user->id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error creating checkout session: ' . $e->getMessage()
            ], 500);
        }
    }

    public function cancel(Request $request): JsonResponse
    {
        $user = Auth::user();


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
