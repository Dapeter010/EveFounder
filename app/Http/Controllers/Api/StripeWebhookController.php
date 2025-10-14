<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\Subscription;
use App\Models\ProfileBoost;
use App\Mail\SubscriptionCreated;
use App\Mail\BoostActivated;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;

class StripeWebhookController extends Controller
{
    /**
     * Handle incoming Stripe webhooks
     */
    public function handle(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $webhookSecret = config('services.stripe.webhook_secret');

        if (!$webhookSecret) {
            Log::error('Stripe webhook secret not configured');
            return response()->json(['error' => 'Webhook secret not configured'], 500);
        }

        try {
            // Verify webhook signature
            $event = Webhook::constructEvent($payload, $sigHeader, $webhookSecret);
        } catch (SignatureVerificationException $e) {
            Log::error('Invalid Stripe webhook signature', [
                'error' => $e->getMessage(),
                'ip' => $request->ip()
            ]);
            return response()->json(['error' => 'Invalid signature'], 400);
        } catch (\Exception $e) {
            Log::error('Error parsing Stripe webhook', [
                'error' => $e->getMessage()
            ]);
            return response()->json(['error' => 'Webhook error'], 400);
        }

        // Log the event
        $this->logWebhookEvent($event);

        // Handle the event
        try {
            $handled = match ($event->type) {
                'payment_intent.succeeded' => $this->handlePaymentIntentSucceeded($event),
                'payment_intent.payment_failed' => $this->handlePaymentIntentFailed($event),
                'customer.subscription.created' => $this->handleSubscriptionCreated($event),
                'customer.subscription.updated' => $this->handleSubscriptionUpdated($event),
                'customer.subscription.deleted' => $this->handleSubscriptionDeleted($event),
                'checkout.session.completed' => $this->handleCheckoutSessionCompleted($event),
                default => ['success' => true, 'message' => 'Event type not handled']
            };

            Log::info('Stripe webhook processed', [
                'event_id' => $event->id,
                'type' => $event->type,
                'result' => $handled
            ]);

            return response()->json($handled);
        } catch (\Exception $e) {
            Log::error('Error handling Stripe webhook', [
                'event_id' => $event->id,
                'type' => $event->type,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Internal server error'
            ], 500);
        }
    }

    /**
     * Handle successful payment intents
     */
    protected function handlePaymentIntentSucceeded($event): array
    {
        $paymentIntent = $event->data->object;
        $metadata = $paymentIntent->metadata ?? [];

        Log::info('Payment intent succeeded', [
            'payment_intent_id' => $paymentIntent->id,
            'amount' => $paymentIntent->amount,
            'metadata' => $metadata
        ]);

        // Handle boost purchases (one-time payments)
        if (isset($metadata->type) && $metadata->type === 'boost_purchase') {
            return $this->handleBoostPayment($paymentIntent, $metadata);
        }

        // Handle subscription purchases via Payment Intent (mobile app)
        if (isset($metadata->type) && $metadata->type === 'subscription') {
            return $this->handleSubscriptionPayment($paymentIntent, $metadata);
        }

        return ['success' => true, 'message' => 'Payment intent processed'];
    }

    /**
     * Handle failed payment intents
     */
    protected function handlePaymentIntentFailed($event): array
    {
        $paymentIntent = $event->data->object;
        $metadata = $paymentIntent->metadata ?? [];

        Log::warning('Payment intent failed', [
            'payment_intent_id' => $paymentIntent->id,
            'amount' => $paymentIntent->amount,
            'failure_message' => $paymentIntent->last_payment_error->message ?? 'Unknown error',
            'metadata' => $metadata
        ]);

        // Handle boost payment failures
        if (isset($metadata->type) && $metadata->type === 'boost_purchase') {
            $userId = $metadata->user_id ?? null;
            $boostId = $metadata->pending_boost_id ?? null;

            if ($boostId) {
                ProfileBoost::where('id', $boostId)->update([
                    'status' => 'failed',
                    'payment_failed_at' => now()
                ]);
            }

            // TODO: Send email notification to user about failed payment
        }

        return ['success' => true, 'message' => 'Payment failure processed'];
    }

    /**
     * Handle subscription creation
     */
    protected function handleSubscriptionCreated($event): array
    {
        $stripeSubscription = $event->data->object;
        $customerId = $stripeSubscription->customer;
        $metadata = $stripeSubscription->metadata ?? [];

        // Find user by Stripe customer ID
        $user = User::where('stripe_customer_id', $customerId)->first();

        if (!$user && isset($metadata->user_id)) {
            $user = User::find($metadata->user_id);
        }

        if (!$user) {
            Log::error('User not found for subscription', [
                'customer_id' => $customerId,
                'subscription_id' => $stripeSubscription->id
            ]);
            return ['success' => false, 'message' => 'User not found'];
        }

        // Update user's Stripe customer ID if not set
        if (!$user->stripe_customer_id) {
            $user->update(['stripe_customer_id' => $customerId]);
        }

        // Determine plan type from price ID
        $priceId = $stripeSubscription->items->data[0]->price->id ?? null;
        $planType = $this->getPlanTypeFromPriceId($priceId);

        // Create subscription record
        Subscription::updateOrCreate(
            ['stripe_subscription_id' => $stripeSubscription->id],
            [
                'user_id' => $user->id,
                'plan_type' => $planType,
                'status' => $stripeSubscription->status,
                'amount' => $stripeSubscription->items->data[0]->price->unit_amount / 100,
                'currency' => strtoupper($stripeSubscription->currency),
                'price_id' => $priceId,
                'starts_at' => now()->timestamp($stripeSubscription->current_period_start),
                'ends_at' => now()->timestamp($stripeSubscription->current_period_end),
            ]
        );

        Log::info('Subscription created', [
            'user_id' => $user->id,
            'subscription_id' => $stripeSubscription->id,
            'plan_type' => $planType
        ]);

        // Send welcome email for subscription
        $newSubscription = Subscription::where('stripe_subscription_id', $stripeSubscription->id)->first();
        if ($newSubscription) {
            Mail::to($user->email)->queue(new SubscriptionCreated($user, $newSubscription));
        }

        return ['success' => true, 'message' => 'Subscription created'];
    }

    /**
     * Handle subscription updates
     */
    protected function handleSubscriptionUpdated($event): array
    {
        $stripeSubscription = $event->data->object;

        $subscription = Subscription::where('stripe_subscription_id', $stripeSubscription->id)->first();

        if (!$subscription) {
            Log::warning('Subscription not found for update', [
                'subscription_id' => $stripeSubscription->id
            ]);
            return ['success' => false, 'message' => 'Subscription not found'];
        }

        // Update subscription details
        $priceId = $stripeSubscription->items->data[0]->price->id ?? $subscription->price_id;
        $planType = $this->getPlanTypeFromPriceId($priceId);

        $subscription->update([
            'plan_type' => $planType,
            'status' => $stripeSubscription->status,
            'amount' => $stripeSubscription->items->data[0]->price->unit_amount / 100,
            'price_id' => $priceId,
            'ends_at' => now()->timestamp($stripeSubscription->current_period_end),
        ]);

        Log::info('Subscription updated', [
            'subscription_id' => $stripeSubscription->id,
            'status' => $stripeSubscription->status,
            'plan_type' => $planType
        ]);

        return ['success' => true, 'message' => 'Subscription updated'];
    }

    /**
     * Handle subscription deletion/cancellation
     */
    protected function handleSubscriptionDeleted($event): array
    {
        $stripeSubscription = $event->data->object;

        $subscription = Subscription::where('stripe_subscription_id', $stripeSubscription->id)->first();

        if (!$subscription) {
            Log::warning('Subscription not found for deletion', [
                'subscription_id' => $stripeSubscription->id
            ]);
            return ['success' => false, 'message' => 'Subscription not found'];
        }

        $subscription->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);

        Log::info('Subscription cancelled', [
            'subscription_id' => $stripeSubscription->id,
            'user_id' => $subscription->user_id
        ]);

        // TODO: Send cancellation confirmation email

        return ['success' => true, 'message' => 'Subscription cancelled'];
    }

    /**
     * Handle checkout session completion
     */
    protected function handleCheckoutSessionCompleted($event): array
    {
        $session = $event->data->object;
        $metadata = $session->metadata ?? [];

        // Handle based on type
        if (isset($metadata->type)) {
            if ($metadata->type === 'boost_purchase') {
                // Handled by payment_intent.succeeded for one-time payments
                return ['success' => true, 'message' => 'Boost checkout handled via payment intent'];
            }

            if ($metadata->type === 'subscription_purchase') {
                // Handled by customer.subscription.created for subscriptions
                return ['success' => true, 'message' => 'Subscription checkout handled via subscription events'];
            }
        }

        return ['success' => true, 'message' => 'Checkout session completed'];
    }

    /**
     * Handle boost payment from payment intent
     */
    protected function handleBoostPayment($paymentIntent, $metadata): array
    {
        $userId = $metadata->user_id ?? null;
        $boostId = $metadata->boost_id ?? $metadata->boost_type ?? null;
        $pendingBoostId = $metadata->pending_boost_id ?? null;

        if (!$userId || !$boostId) {
            Log::error('Missing boost payment metadata', [
                'payment_intent_id' => $paymentIntent->id,
                'metadata' => $metadata
            ]);
            return ['success' => false, 'message' => 'Missing metadata'];
        }

        $user = User::find($userId);
        if (!$user) {
            return ['success' => false, 'message' => 'User not found'];
        }

        // Get boost configuration
        $boostConfig = $this->getBoostConfig($boostId);
        if (!$boostConfig) {
            return ['success' => false, 'message' => 'Invalid boost type'];
        }

        // Check for existing active boost
        $activeBoost = ProfileBoost::where('user_id', $userId)
            ->where('status', 'active')
            ->where('ends_at', '>', now())
            ->first();

        if ($activeBoost) {
            Log::warning('User already has active boost', [
                'user_id' => $userId,
                'active_boost_id' => $activeBoost->id
            ]);
            return ['success' => false, 'message' => 'User already has active boost'];
        }

        // Activate the boost
        $startsAt = now();
        $endsAt = $startsAt->copy()->addMinutes($boostConfig['duration']);

        // For mobile Payment Intent flow, create new boost (no pending boost)
        // For web Checkout Session flow, update existing pending boost
        $boost = $pendingBoostId
            ? ProfileBoost::updateOrCreate(
                ['id' => $pendingBoostId],
                [
                    'user_id' => $userId,
                    'boost_type' => $boostId,
                    'cost' => $boostConfig['price'],
                    'starts_at' => $startsAt,
                    'ends_at' => $endsAt,
                    'status' => 'active',
                    'stripe_payment_intent_id' => $paymentIntent->id,
                    'payment_completed_at' => now(),
                    'views_gained' => 0,
                    'likes_gained' => 0,
                    'matches_gained' => 0,
                ]
            )
            : ProfileBoost::create([
                'user_id' => $userId,
                'boost_type' => $boostId,
                'cost' => $boostConfig['price'],
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'status' => 'active',
                'stripe_payment_intent_id' => $paymentIntent->id,
                'payment_completed_at' => now(),
                'views_gained' => 0,
                'likes_gained' => 0,
                'matches_gained' => 0,
            ]);

        Log::info('Boost activated from payment intent', [
            'user_id' => $userId,
            'boost_id' => $boost->id,
            'boost_type' => $boostId,
            'payment_intent_id' => $paymentIntent->id
        ]);

        // Send boost activation email
        Mail::to($user->email)->queue(new BoostActivated($user, $boost));

        return ['success' => true, 'message' => 'Boost activated', 'boost_id' => $boost->id];
    }

    /**
     * Handle subscription payment from payment intent (mobile app)
     */
    protected function handleSubscriptionPayment($paymentIntent, $metadata): array
    {
        $userId = $metadata->user_id ?? null;
        $planType = $metadata->plan_type ?? 'basic';

        if (!$userId) {
            Log::error('Missing subscription payment metadata', [
                'payment_intent_id' => $paymentIntent->id,
                'metadata' => $metadata
            ]);
            return ['success' => false, 'message' => 'Missing user ID'];
        }

        $user = User::find($userId);
        if (!$user) {
            return ['success' => false, 'message' => 'User not found'];
        }

        // Get subscription configuration
        [$cost, $name] = match ($planType) {
            'basic' => [9.99, 'Basic Subscription'],
            'premium' => [19.99, 'Premium Subscription'],
            default => [9.99, 'Basic Subscription'],
        };

        // Create or update subscription record
        $startsAt = now();
        $endsAt = $startsAt->copy()->addMonth();

        $subscription = Subscription::updateOrCreate(
            ['user_id' => $userId],
            [
                'plan_type' => $planType,
                'status' => 'active',
                'amount' => $cost,
                'currency' => 'GBP',
                'stripe_payment_intent_id' => $paymentIntent->id,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
            ]
        );

        Log::info('Subscription activated from payment intent', [
            'user_id' => $userId,
            'subscription_id' => $subscription->id,
            'plan_type' => $planType,
            'payment_intent_id' => $paymentIntent->id
        ]);

        // Send subscription activation email
        Mail::to($user->email)->queue(new SubscriptionCreated($user, $subscription));

        return ['success' => true, 'message' => 'Subscription activated', 'subscription_id' => $subscription->id];
    }

    /**
     * Get boost configuration by ID
     */
    protected function getBoostConfig(string $boostId): ?array
    {
        $configs = [
            'profile' => ['price' => 4.99, 'duration' => 30],
            'super' => ['price' => 9.99, 'duration' => 180],
            'weekend' => ['price' => 14.99, 'duration' => 2880],
        ];

        return $configs[$boostId] ?? null;
    }

    /**
     * Determine plan type from Stripe price ID
     */
    protected function getPlanTypeFromPriceId(?string $priceId): string
    {
        if (!$priceId) {
            return 'free';
        }

        // Match against known price IDs
        $premiumPriceId = config('services.stripe.premium_price_id', 'price_1RvJUZGhlS5RvknCyv6vX6lT');
        $basicPriceId = config('services.stripe.basic_price_id', 'price_1RvJSxGhlS5RvknCWKGwanha');

        if ($priceId === $premiumPriceId) {
            return 'premium';
        }

        if ($priceId === $basicPriceId) {
            return 'basic';
        }

        return 'free';
    }

    /**
     * Log webhook event for debugging and auditing
     */
    protected function logWebhookEvent($event): void
    {
        try {
            \DB::table('stripe_events')->insert([
                'stripe_event_id' => $event->id,
                'type' => $event->type,
                'payload' => json_encode($event->data->object),
                'processed' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Exception $e) {
            // Table might not exist yet, just log to file
            Log::info('Stripe webhook event', [
                'event_id' => $event->id,
                'type' => $event->type
            ]);
        }
    }
}
