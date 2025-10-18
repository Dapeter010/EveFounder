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
            $table->timestamp('expires_at')->nullable()->after('read_at')->comment('When message should be auto-deleted');
            $table->boolean('auto_delete_after_read')->default(false)->after('expires_at')->comment('Delete message after being read');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn(['expires_at', 'auto_delete_after_read']);
        });
    }
};
