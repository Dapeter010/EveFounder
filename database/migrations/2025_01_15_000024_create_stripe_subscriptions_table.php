<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('stripe_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->string('customer_id')->unique();
            $table->string('subscription_id')->nullable();
            $table->string('price_id')->nullable();
            $table->bigInteger('current_period_start')->nullable();
            $table->bigInteger('current_period_end')->nullable();
            $table->boolean('cancel_at_period_end')->default(false);
            $table->string('payment_method_brand')->nullable();
            $table->string('payment_method_last4')->nullable();
            $table->enum('status', [
                'not_started',
                'incomplete',
                'incomplete_expired',
                'trialing',
                'active',
                'past_due',
                'canceled',
                'unpaid',
                'paused'
            ]);
            $table->timestamps();
            $table->timestamp('deleted_at')->nullable();
            
            $table->index('customer_id');
            $table->index('subscription_id');
            $table->index('status');
        });
    }

    public function down()
    {
        Schema::dropIfExists('stripe_subscriptions');
    }
};