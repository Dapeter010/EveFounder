<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('content_moderation', function (Blueprint $table) {
            $table->id();
            $table->uuid('user_id');
            $table->enum('content_type', ['photo', 'bio']);
            $table->text('content_url')->nullable(); // For photos
            $table->text('content_text')->nullable(); // For bios
            $table->enum('status', ['pending', 'approved', 'rejected', 'flagged'])->default('pending');
            $table->decimal('ai_score', 3, 2)->nullable(); // AI moderation score 0.00-1.00
            $table->json('ai_flags')->nullable(); // Array of AI-detected issues
            $table->text('admin_notes')->nullable();
            $table->uuid('reviewed_by')->nullable(); // Admin who reviewed
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            
            $table->index(['status', 'content_type']);
            $table->index('user_id');
            $table->index('created_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('content_moderation');
    }
};