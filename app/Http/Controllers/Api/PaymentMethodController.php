<?php

namespace App\Http\Controllers\Api;

use App\Services\StripeService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;

class PaymentMethodController extends Controller
{
    protected StripeService $stripeService;

    public function __construct(StripeService $stripeService)
    {
        $this->stripeService = $stripeService;
    }

    /**
     * Get all payment methods for authenticated user
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated'
            ], 401);
        }

        $paymentMethods = $this->stripeService->getPaymentMethods($user);

        $formattedMethods = array_map(function ($method) {
            return [
                'id' => $method->id,
                'type' => $method->type,
                'card' => [
                    'brand' => $method->card->brand,
                    'last4' => $method->card->last4,
                    'exp_month' => $method->card->exp_month,
                    'exp_year' => $method->card->exp_year,
                ],
                'created' => $method->created,
            ];
        }, $paymentMethods);

        return response()->json([
            'success' => true,
            'data' => $formattedMethods
        ]);
    }

    /**
     * Create setup intent for adding new payment method
     */
    public function createSetupIntent(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated'
            ], 401);
        }

        $setupIntent = $this->stripeService->createSetupIntent($user);

        if (!$setupIntent) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create setup intent'
            ], 500);
        }

        return response()->json([
            'success' => true,
            'data' => $setupIntent
        ]);
    }

    /**
     * Attach payment method to customer
     */
    public function attach(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'payment_method_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => implode(", ", $validator->errors()->all()),
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

        $paymentMethod = $this->stripeService->attachPaymentMethod(
            $user,
            $request->payment_method_id
        );

        if (!$paymentMethod) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to attach payment method'
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Payment method added successfully',
            'data' => [
                'id' => $paymentMethod->id,
                'type' => $paymentMethod->type,
                'card' => [
                    'brand' => $paymentMethod->card->brand,
                    'last4' => $paymentMethod->card->last4,
                    'exp_month' => $paymentMethod->card->exp_month,
                    'exp_year' => $paymentMethod->card->exp_year,
                ],
            ]
        ]);
    }

    /**
     * Detach payment method from customer
     */
    public function detach(Request $request, string $paymentMethodId): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated'
            ], 401);
        }

        $success = $this->stripeService->detachPaymentMethod($paymentMethodId);

        if (!$success) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove payment method'
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Payment method removed successfully'
        ]);
    }

    /**
     * Set default payment method
     */
    public function setDefault(Request $request, string $paymentMethodId): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated'
            ], 401);
        }

        if (!$user->stripe_customer_id) {
            return response()->json([
                'success' => false,
                'message' => 'No Stripe customer found'
            ], 404);
        }

        try {
            \Stripe\Customer::update($user->stripe_customer_id, [
                'invoice_settings' => [
                    'default_payment_method' => $paymentMethodId,
                ],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Default payment method updated'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to set default payment method'
            ], 500);
        }
    }
}
