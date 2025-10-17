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
use Stripe\Customer;
use Stripe\EphemeralKey;
use Stripe\PaymentIntent;
use Stripe\Stripe;
use Stripe\Checkout\Session as StripeSession;

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
            'metadata' => 'sometimes|array', // Accept but ignore - we build our own from auth user
            'promo_code' => 'nullable|string',
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

        // Handle promo code if provided
        $promoCode = null;
        if ($request->promo_code) {
            $promoCode = \App\Models\PromoCode::where('code', strtoupper($request->promo_code))
                ->active()
                ->applicableTo('subscription')
                ->first();

            if (!$promoCode || !$promoCode->canBeUsedBy($user->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid or expired promo code',
                ], 400);
            }
        }

        // Create Stripe checkout session directly in Laravel
        try {
            Stripe::setApiKey(config('services.stripe.secret'));

            // Get or create Stripe customer
            $customer = null;
            if ($user->stripe_customer_id) {
                try {
                    $customer = Customer::retrieve($user->stripe_customer_id);
                } catch (\Exception $e) {
                    Log::warning('Failed to retrieve Stripe customer', [
                        'stripe_customer_id' => $user->stripe_customer_id,
                        'error' => $e->getMessage()
                    ]);
                    $customer = null;
                }
            }

            if (!$customer) {
                $customer = Customer::create([
                    'email' => $user->email,
                    'name' => trim($user->first_name . ' ' . $user->last_name),
                    'metadata' => [
                        'user_id' => $user->uid,
                        'user_email' => $user->email,
                    ],
                ]);

                // Store customer ID in database
                $user->update(['stripe_customer_id' => $customer->id]);

                Log::info('Created Stripe customer', [
                    'user_id' => $user->id,
                    'stripe_customer_id' => $customer->id
                ]);
            }

            // Create checkout session
            $sessionParams = [
                'customer' => $customer->id,
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price' => $request->price_id,
                    'quantity' => 1,
                ]],
                'mode' => $request->mode,
                'success_url' => $request->success_url . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => $request->cancel_url,
                'metadata' => [
                    'user_id' => $user->uid,
                    'user_email' => $user->email,
                    'type' => $request->mode === 'subscription' ? 'subscription_purchase' : 'one_time_payment',
                    'promo_code_id' => $promoCode ? $promoCode->id : null,
                    'promo_code' => $promoCode ? $promoCode->code : null,
                ],
            ];

            // Apply promo code discount if provided (via coupon in Stripe or manual calculation)
            if ($promoCode) {
                $sessionParams['metadata']['has_promo_code'] = 'true';
                // Note: Actual discount application happens in webhook after payment
            }

            // For subscription mode, add subscription-specific params
            if ($request->mode === 'subscription') {
                $sessionParams['subscription_data'] = [
                    'metadata' => [
                        'user_id' => $user->uid,
                        'user_email' => $user->email,
                    ]
                ];
            }

            $session = StripeSession::create($sessionParams);

            Log::info('Stripe checkout session created', [
                'session_id' => $session->id,
                'user_id' => $user->id,
                'mode' => $request->mode,
                'price_id' => $request->price_id
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'sessionId' => $session->id,
                    'url' => $session->url
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Exception creating Stripe checkout session', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
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
            Stripe::setApiKey(config('services.stripe.secret'));

            // Get or create Stripe customer
            $customer = null;
            if ($user->stripe_customer_id) {
                try {
                    $customer = Customer::retrieve($user->stripe_customer_id);
                } catch (\Exception $e) {
                    $customer = null;
                }
            }

            if (!$customer) {
                $customer = Customer::create([
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
            $ephemeralKey = EphemeralKey::create(
                ['customer' => $customer->id],
                ['stripe_version' => '2024-11-20.acacia']
            );

            // For subscriptions, create payment intent for first payment
            $paymentIntent = PaymentIntent::create([
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
