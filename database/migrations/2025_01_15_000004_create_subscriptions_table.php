<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('plan_type', ['basic', 'premium']);
            $table->enum('status', ['active', 'cancelled', 'expired', 'pending']);
            $table->decimal('amount', 8, 2);
            $table->string('currency', 3)->default('GBP');
            $table->string('stripe_subscription_id')->nullable();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'status']);
            $table->index('ends_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('subscriptions');
    }
};