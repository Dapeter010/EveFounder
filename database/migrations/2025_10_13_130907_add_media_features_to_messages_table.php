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
        Schema::table('messages', function (Blueprint $table) {
            // Change type enum to include 'video'
            $table->enum('type', ['text', 'image', 'gif', 'video'])->default('text')->change();

            // Add media URL for images and videos
            $table->string('media_url')->nullable()->after('content');

            // Add view once feature
            $table->boolean('view_once')->default(false)->after('media_url');
            $table->timestamp('viewed_at')->nullable()->after('view_once');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            // Remove added columns
            $table->dropColumn(['media_url', 'view_once', 'viewed_at']);

            // Revert type enum to original values
            $table->enum('type', ['text', 'image', 'gif'])->default('text')->change();
        });
    }
};
