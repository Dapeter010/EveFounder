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
        Schema::create('call_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('call_id')->constrained('calls')->onDelete('cascade');
            $table->enum('event_type', [
                'initiated',
                'ringing',
                'accepted',
                'declined',
                'missed',
                'ended',
                'failed',
                'ice_candidate',
                'offer',
                'answer'
            ]);
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->json('metadata')->nullable()->comment('Additional event data (ICE candidates, SDP, error info)');
            $table->timestamp('created_at');

            // Indexes for efficient queries
            $table->index('call_id');
            $table->index('event_type');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('call_events');
    }
};
