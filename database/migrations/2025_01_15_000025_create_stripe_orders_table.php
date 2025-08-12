<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('stripe_orders', function (Blueprint $table) {
            $table->id();
            $table->string('checkout_session_id');
            $table->string('payment_intent_id');
            $table->string('customer_id');
            $table->bigInteger('amount_subtotal');
            $table->bigInteger('amount_total');
            $table->string('currency');
            $table->string('payment_status');
            $table->enum('status', ['pending', 'completed', 'canceled'])->default('pending');
            $table->timestamps();
            $table->timestamp('deleted_at')->nullable();
            
            $table->index('customer_id');
            $table->index('checkout_session_id');
            $table->index('payment_intent_id');
            $table->index('status');
        });
    }

    public function down()
    {
        Schema::dropIfExists('stripe_orders');
    }
};