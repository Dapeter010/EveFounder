<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PromoCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PromoCodeController extends Controller
{
    /**
     * Validate promo code for user (public endpoint)
     */
    public function validate(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string',
            'type' => 'required|in:subscription,boost',
            'plan_type' => 'nullable|in:basic,premium',
            'original_price' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $code = strtoupper($request->code);
        $type = $request->type;
        $planType = $request->plan_type;
        $originalPrice = (float) $request->original_price;

        // Find promo code
        $promoCode = PromoCode::where('code', $code)
            ->active()
            ->applicableTo($type)
            ->first();

        if (!$promoCode) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired promo code',
            ], 404);
        }

        // Check if user already used this code
        $userId = auth()->id();
        if ($userId && $promoCode->hasBeenUsedBy($userId)) {
            return response()->json([
                'success' => false,
                'message' => 'You have already used this promo code',
            ], 400);
        }

        // Check plan restriction
        if (!$promoCode->canBeAppliedTo($type, $planType)) {
            return response()->json([
                'success' => false,
                'message' => 'This promo code cannot be applied to this plan',
            ], 400);
        }

        // Calculate discount
        $discountAmount = $promoCode->calculateDiscount($originalPrice);
        $finalPrice = $promoCode->getFinalPrice($originalPrice);

        return response()->json([
            'success' => true,
            'data' => [
                'code' => $promoCode->code,
                'type' => $promoCode->type,
                'discount_value' => $promoCode->discount_value,
                'discount_amount' => $discountAmount,
                'original_price' => $originalPrice,
                'final_price' => $finalPrice,
                'duration_in_months' => $promoCode->duration_in_months,
                'description' => $promoCode->description,
            ],
        ]);
    }

    /**
     * Get all promo codes (admin)
     */
    public function index(Request $request): JsonResponse
    {
        $query = PromoCode::query()->with('usages');

        // Filter by status
        if ($request->has('status')) {
            if ($request->status === 'active') {
                $query->active();
            } elseif ($request->status === 'expired') {
                $query->where('is_active', false)
                    ->orWhere('expires_at', '<', now());
            }
        }

        // Search by code
        if ($request->has('search')) {
            $query->where('code', 'like', '%' . strtoupper($request->search) . '%');
        }

        $promoCodes = $query->latest()->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $promoCodes,
        ]);
    }

    /**
     * Create new promo code (admin)
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string|max:50|unique:promo_codes,code',
            'description' => 'nullable|string',
            'type' => 'required|in:percentage,fixed_amount,free_trial',
            'discount_value' => 'nullable|numeric|min:0',
            'duration_in_months' => 'nullable|integer|min:1',
            'applicable_to' => 'required|in:subscription,boost,both',
            'plan_restriction' => 'nullable|in:basic,premium',
            'max_uses' => 'nullable|integer|min:1',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after_or_equal:starts_at',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Validate discount_value based on type
        if ($request->type !== 'free_trial' && empty($request->discount_value)) {
            return response()->json([
                'success' => false,
                'message' => 'Discount value is required for this promo type',
            ], 422);
        }

        if ($request->type === 'percentage' && $request->discount_value > 100) {
            return response()->json([
                'success' => false,
                'message' => 'Percentage discount cannot exceed 100%',
            ], 422);
        }

        $promoCode = PromoCode::create([
            'code' => strtoupper($request->code),
            'description' => $request->description,
            'type' => $request->type,
            'discount_value' => $request->discount_value,
            'duration_in_months' => $request->duration_in_months,
            'applicable_to' => $request->applicable_to,
            'plan_restriction' => $request->plan_restriction,
            'max_uses' => $request->max_uses,
            'starts_at' => $request->starts_at,
            'expires_at' => $request->expires_at,
            'is_active' => $request->is_active ?? true,
            'created_by' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Promo code created successfully',
            'data' => $promoCode->load('usages'),
        ], 201);
    }

    /**
     * Get single promo code (admin)
     */
    public function show(int $id): JsonResponse
    {
        $promoCode = PromoCode::with('usages.user')->find($id);

        if (!$promoCode) {
            return response()->json([
                'success' => false,
                'message' => 'Promo code not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $promoCode,
        ]);
    }

    /**
     * Update promo code (admin)
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $promoCode = PromoCode::find($id);

        if (!$promoCode) {
            return response()->json([
                'success' => false,
                'message' => 'Promo code not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'code' => 'sometimes|string|max:50|unique:promo_codes,code,' . $id,
            'description' => 'nullable|string',
            'type' => 'sometimes|in:percentage,fixed_amount,free_trial',
            'discount_value' => 'nullable|numeric|min:0',
            'duration_in_months' => 'nullable|integer|min:1',
            'applicable_to' => 'sometimes|in:subscription,boost,both',
            'plan_restriction' => 'nullable|in:basic,premium',
            'max_uses' => 'nullable|integer|min:1',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after_or_equal:starts_at',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        if ($request->has('code')) {
            $request->merge(['code' => strtoupper($request->code)]);
        }

        $promoCode->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Promo code updated successfully',
            'data' => $promoCode->fresh()->load('usages'),
        ]);
    }

    /**
     * Delete promo code (admin)
     */
    public function destroy(int $id): JsonResponse
    {
        $promoCode = PromoCode::find($id);

        if (!$promoCode) {
            return response()->json([
                'success' => false,
                'message' => 'Promo code not found',
            ], 404);
        }

        // Prevent deletion if code has been used
        if ($promoCode->current_uses > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete promo code that has been used. Consider deactivating it instead.',
            ], 400);
        }

        $promoCode->delete();

        return response()->json([
            'success' => true,
            'message' => 'Promo code deleted successfully',
        ]);
    }

    /**
     * Toggle promo code active status (admin)
     */
    public function toggleActive(int $id): JsonResponse
    {
        $promoCode = PromoCode::find($id);

        if (!$promoCode) {
            return response()->json([
                'success' => false,
                'message' => 'Promo code not found',
            ], 404);
        }

        $promoCode->update(['is_active' => !$promoCode->is_active]);

        return response()->json([
            'success' => true,
            'message' => $promoCode->is_active ? 'Promo code activated' : 'Promo code deactivated',
            'data' => $promoCode,
        ]);
    }

    /**
     * Get promo code statistics (admin)
     */
    public function stats(): JsonResponse
    {
        $stats = [
            'total_codes' => PromoCode::count(),
            'active_codes' => PromoCode::active()->count(),
            'total_uses' => PromoCode::sum('current_uses'),
            'total_revenue_impact' => \App\Models\PromoCodeUsage::sum('discount_amount'),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }
}
