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
                'price_id' => $subscription->price_id,
                'next_billing' => $subscription->ends_at,
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
            'mode' => 'required|in:payment,subscription',
            'metadata' => 'sometimes|array' // Accept but ignore - we build our own from auth user
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => implode(",", $validator->errors()->all()),
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
                    'user_id' => $user->uid,
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

    /**
     * Create Payment Intent for in-app Flutter Stripe subscriptions
     */
    public function createPaymentIntent(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'plan_type' => 'required|in:basic,premium',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => implode(",", $validator->errors()->all()),
            ], 422);
        }

        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated'
            ], 401);
        }

        // Get plan details
        [$cost, $name] = match ($request->plan_type) {
            'basic' => [9.99, 'Basic Subscription'],
            'premium' => [19.99, 'Premium Subscription'],
            default => [9.99, 'Basic Subscription'],
        };

        // Create Stripe Payment Intent
        try {
            \Stripe\Stripe::setApiKey(config('services.stripe.secret'));

            // Get or create Stripe customer
            $customer = null;
            if ($user->stripe_customer_id) {
                try {
                    $customer = \Stripe\Customer::retrieve($user->stripe_customer_id);
                } catch (\Exception $e) {
                    $customer = null;
                }
            }

            if (!$customer) {
                $customer = \Stripe\Customer::create([
                    'email' => $user->email,
                    'name' => trim($user->first_name . ' ' . $user->last_name),
                    'metadata' => [
                        'user_id' => $user->uid,
                    ],
                ]);

                // Store customer ID
                $user->update(['stripe_customer_id' => $customer->id]);
            }

            // Create ephemeral key for customer
            $ephemeralKey = \Stripe\EphemeralKey::create(
                ['customer' => $customer->id],
                ['stripe_version' => '2024-11-20.acacia']
            );

            // For subscriptions, create payment intent for first payment
            $paymentIntent = \Stripe\PaymentIntent::create([
                'amount' => $cost * 100, // Convert to pence
                'currency' => 'gbp',
                'customer' => $customer->id,
                'description' => $name . ' - Monthly',
                'setup_future_usage' => 'off_session', // For recurring payments
                'metadata' => [
                    'user_id' => $user->uid,
                    'plan_type' => $request->plan_type,
                    'type' => 'subscription',
                ],
                'automatic_payment_methods' => [
                    'enabled' => true,
                ],
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'clientSecret' => $paymentIntent->client_secret,
                    'ephemeralKey' => $ephemeralKey->secret,
                    'customer' => $customer->id,
                    'paymentIntentId' => $paymentIntent->id,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to create subscription payment intent', [
                'error' => $e->getMessage(),
                'plan_type' => $request->plan_type,
                'user_id' => $user->id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create subscription payment intent: ' . $e->getMessage(),
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
