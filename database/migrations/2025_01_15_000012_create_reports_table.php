<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reporter_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('reported_id')->constrained('users')->onDelete('cascade');
            $table->enum('type', ['inappropriate_behavior', 'fake_profile', 'harassment', 'spam', 'other']);
            $table->text('reason');
            $table->text('description')->nullable();
            $table->json('evidence')->nullable(); // Screenshots, message IDs, etc.
            $table->enum('status', ['pending', 'investigating', 'resolved', 'dismissed'])->default('pending');
            $table->text('admin_notes')->nullable();
            $table->foreignId('handled_by')->nullable()->constrained('users');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            
            $table->index(['status', 'created_at']);
            $table->index('reported_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('reports');
    }
};