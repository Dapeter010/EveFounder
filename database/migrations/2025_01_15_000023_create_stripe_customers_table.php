<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('stripe_customers', function (Blueprint $table) {
            $table->id();
            $table->uuid('user_id')->unique(); // References auth.users from Supabase
            $table->string('customer_id')->unique();
            $table->timestamps();
            $table->timestamp('deleted_at')->nullable();
            
            $table->index('user_id');
            $table->index('customer_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('stripe_customers');
    }
};