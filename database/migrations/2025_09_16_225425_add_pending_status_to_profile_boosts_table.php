<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;


return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('profile_boosts', function (Blueprint $table) {
            DB::statement("ALTER TABLE profile_boosts MODIFY COLUMN status ENUM('pending', 'active', 'completed', 'cancelled') DEFAULT 'pending'");

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('profile_boosts', function (Blueprint $table) {
            DB::statement("ALTER TABLE profile_boosts MODIFY COLUMN status ENUM('active', 'completed', 'cancelled') DEFAULT 'active'");
        });
    }
};
