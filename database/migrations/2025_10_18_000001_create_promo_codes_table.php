<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('promo_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique()->index();
            $table->text('description')->nullable();
            $table->enum('type', ['percentage', 'fixed_amount', 'free_trial'])->default('percentage');
            $table->decimal('discount_value', 8, 2)->nullable(); // Percentage (0-100) or fixed amount
            $table->integer('duration_in_months')->nullable(); // For free trials or limited discount periods
            $table->enum('applicable_to', ['subscription', 'boost', 'both'])->default('subscription');
            $table->enum('plan_restriction', ['basic', 'premium'])->nullable(); // null = applies to all plans
            $table->integer('max_uses')->nullable(); // null = unlimited
            $table->integer('current_uses')->default(0);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable(); // Admin user ID
            $table->timestamps();

            $table->index(['is_active', 'expires_at']);
            $table->index('current_uses');
        });

        Schema::create('promo_code_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('promo_code_id')->constrained('promo_codes')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('subscription_id')->nullable()->constrained('subscriptions')->onDelete('set null');
            $table->foreignId('boost_id')->nullable()->constrained('profile_boosts')->onDelete('set null');
            $table->decimal('discount_amount', 8, 2); // Actual discount applied in GBP
            $table->timestamp('used_at');
            $table->timestamps();

            // Prevent user from using same promo code multiple times
            $table->unique(['promo_code_id', 'user_id']);
            $table->index('user_id');
            $table->index('used_at');
        });

        // Add promo code tracking to subscriptions table
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->foreignId('promo_code_id')->nullable()->after('stripe_subscription_id')->constrained('promo_codes')->onDelete('set null');
            $table->decimal('original_amount', 8, 2)->nullable()->after('amount'); // Price before discount
            $table->decimal('discount_amount', 8, 2)->nullable()->after('original_amount'); // Discount applied
        });

        // Add promo code tracking to profile_boosts table
        Schema::table('profile_boosts', function (Blueprint $table) {
            $table->foreignId('promo_code_id')->nullable()->after('cost')->constrained('promo_codes')->onDelete('set null');
            $table->decimal('original_cost', 8, 2)->nullable()->after('promo_code_id'); // Price before discount
            $table->decimal('discount_amount', 8, 2)->nullable()->after('original_cost'); // Discount applied
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('profile_boosts', function (Blueprint $table) {
            $table->dropForeign(['promo_code_id']);
            $table->dropColumn(['promo_code_id', 'original_cost', 'discount_amount']);
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropForeign(['promo_code_id']);
            $table->dropColumn(['promo_code_id', 'original_amount', 'discount_amount']);
        });

        Schema::dropIfExists('promo_code_usages');
        Schema::dropIfExists('promo_codes');
    }
};
