<?php

namespace App\Services;

use App\Models\User;
use Stripe\Stripe;
use Stripe\Customer;
use Stripe\PaymentMethod;
use Stripe\Exception\ApiErrorException;
use Illuminate\Support\Facades\Log;

class StripeService
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    /**
     * Get or create Stripe customer for user
     */
    public function getOrCreateCustomer(User $user): ?Customer
    {
        // If user already has Stripe customer ID, retrieve it
        if ($user->stripe_customer_id) {
            try {
                return Customer::retrieve($user->stripe_customer_id);
            } catch (ApiErrorException $e) {
                Log::warning('Failed to retrieve Stripe customer', [
                    'user_id' => $user->id,
                    'stripe_customer_id' => $user->stripe_customer_id,
                    'error' => $e->getMessage()
                ]);
                // Continue to create new customer if retrieval fails
            }
        }

        // Create new Stripe customer
        try {
            $customer = Customer::create([
                'email' => $user->email,
                'name' => $user->first_name . ' ' . $user->last_name,
                'metadata' => [
                    'user_id' => $user->id,
                    'uid' => $user->uid,
                ],
            ]);

            // Save Stripe customer ID to user
            $user->update(['stripe_customer_id' => $customer->id]);

            Log::info('Created Stripe customer', [
                'user_id' => $user->id,
                'customer_id' => $customer->id
            ]);

            return $customer;
        } catch (ApiErrorException $e) {
            Log::error('Failed to create Stripe customer', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Update Stripe customer information
     */
    public function updateCustomer(User $user, array $data = []): ?Customer
    {
        if (!$user->stripe_customer_id) {
            return $this->getOrCreateCustomer($user);
        }

        try {
            $updateData = array_merge([
                'email' => $user->email,
                'name' => $user->first_name . ' ' . $user->last_name,
            ], $data);

            $customer = Customer::update($user->stripe_customer_id, $updateData);

            Log::info('Updated Stripe customer', [
                'user_id' => $user->id,
                'customer_id' => $customer->id
            ]);

            return $customer;
        } catch (ApiErrorException $e) {
            Log::error('Failed to update Stripe customer', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Delete Stripe customer
     */
    public function deleteCustomer(User $user): bool
    {
        if (!$user->stripe_customer_id) {
            return true;
        }

        try {
            Customer::delete($user->stripe_customer_id);

            $user->update(['stripe_customer_id' => null]);

            Log::info('Deleted Stripe customer', [
                'user_id' => $user->id,
                'customer_id' => $user->stripe_customer_id
            ]);

            return true;
        } catch (ApiErrorException $e) {
            Log::error('Failed to delete Stripe customer', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Attach payment method to customer
     */
    public function attachPaymentMethod(User $user, string $paymentMethodId): ?PaymentMethod
    {
        $customer = $this->getOrCreateCustomer($user);

        if (!$customer) {
            return null;
        }

        try {
            $paymentMethod = PaymentMethod::retrieve($paymentMethodId);
            $paymentMethod->attach(['customer' => $customer->id]);

            // Set as default payment method
            Customer::update($customer->id, [
                'invoice_settings' => [
                    'default_payment_method' => $paymentMethodId,
                ],
            ]);

            Log::info('Attached payment method', [
                'user_id' => $user->id,
                'payment_method_id' => $paymentMethodId
            ]);

            return $paymentMethod;
        } catch (ApiErrorException $e) {
            Log::error('Failed to attach payment method', [
                'user_id' => $user->id,
                'payment_method_id' => $paymentMethodId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Detach payment method from customer
     */
    public function detachPaymentMethod(string $paymentMethodId): bool
    {
        try {
            $paymentMethod = PaymentMethod::retrieve($paymentMethodId);
            $paymentMethod->detach();

            Log::info('Detached payment method', [
                'payment_method_id' => $paymentMethodId
            ]);

            return true;
        } catch (ApiErrorException $e) {
            Log::error('Failed to detach payment method', [
                'payment_method_id' => $paymentMethodId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get customer's payment methods
     */
    public function getPaymentMethods(User $user): array
    {
        if (!$user->stripe_customer_id) {
            return [];
        }

        try {
            $paymentMethods = PaymentMethod::all([
                'customer' => $user->stripe_customer_id,
                'type' => 'card',
            ]);

            return $paymentMethods->data;
        } catch (ApiErrorException $e) {
            Log::error('Failed to retrieve payment methods', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Create Setup Intent for adding payment method
     */
    public function createSetupIntent(User $user): ?array
    {
        $customer = $this->getOrCreateCustomer($user);

        if (!$customer) {
            return null;
        }

        try {
            $setupIntent = \Stripe\SetupIntent::create([
                'customer' => $customer->id,
                'payment_method_types' => ['card'],
            ]);

            return [
                'client_secret' => $setupIntent->client_secret,
                'customer_id' => $customer->id,
            ];
        } catch (ApiErrorException $e) {
            Log::error('Failed to create setup intent', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Create Payment Intent for one-time payments (boosts, etc.)
     */
    public function createPaymentIntent(User $user, int $amount, string $currency = 'gbp', array $metadata = []): ?array
    {
        $customer = $this->getOrCreateCustomer($user);

        if (!$customer) {
            return null;
        }

        try {
            $paymentIntent = \Stripe\PaymentIntent::create([
                'amount' => $amount * 100, // Convert to cents
                'currency' => $currency,
                'customer' => $customer->id,
                'metadata' => array_merge([
                    'user_id' => $user->id,
                    'uid' => $user->uid,
                ], $metadata),
                'automatic_payment_methods' => [
                    'enabled' => true,
                ],
            ]);

            return [
                'client_secret' => $paymentIntent->client_secret,
                'payment_intent_id' => $paymentIntent->id,
            ];
        } catch (ApiErrorException $e) {
            Log::error('Failed to create payment intent', [
                'user_id' => $user->id,
                'amount' => $amount,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Cancel subscription
     */
    public function cancelSubscription(string $subscriptionId): bool
    {
        try {
            \Stripe\Subscription::update($subscriptionId, [
                'cancel_at_period_end' => true,
            ]);

            Log::info('Cancelled subscription', [
                'subscription_id' => $subscriptionId
            ]);

            return true;
        } catch (ApiErrorException $e) {
            Log::error('Failed to cancel subscription', [
                'subscription_id' => $subscriptionId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Immediately cancel subscription
     */
    public function cancelSubscriptionImmediately(string $subscriptionId): bool
    {
        try {
            \Stripe\Subscription::cancel($subscriptionId);

            Log::info('Immediately cancelled subscription', [
                'subscription_id' => $subscriptionId
            ]);

            return true;
        } catch (ApiErrorException $e) {
            Log::error('Failed to immediately cancel subscription', [
                'subscription_id' => $subscriptionId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}
