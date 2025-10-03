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
        // Step 1: Convert enum to string to allow any value temporarily
        if (DB::getDriverName() === 'sqlite') {
            Schema::table('users', function (Blueprint $table) {
                $table->string('relationship_type_temp')->nullable();
            });
            DB::statement("UPDATE users SET relationship_type_temp = relationship_type");
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('relationship_type');
            });
            Schema::table('users', function (Blueprint $table) {
                $table->string('relationship_type')->nullable();
            });
            DB::statement("UPDATE users SET relationship_type = relationship_type_temp");
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('relationship_type_temp');
            });
        } else {
            // For MySQL: Convert ENUM to VARCHAR first
            DB::statement("ALTER TABLE users MODIFY relationship_type VARCHAR(50) NULL");
        }

        // Step 2: Map old values to new values
        DB::statement("UPDATE users SET relationship_type = 'long-term' WHERE relationship_type = 'serious-relationship'");
        DB::statement("UPDATE users SET relationship_type = 'friendship' WHERE relationship_type = 'friends'");

        // Step 3: Convert back to ENUM with new values
        if (DB::getDriverName() === 'sqlite') {
            Schema::table('users', function (Blueprint $table) {
                $table->string('relationship_type_temp')->nullable();
            });
            DB::statement("UPDATE users SET relationship_type_temp = relationship_type");
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('relationship_type');
            });
            Schema::table('users', function (Blueprint $table) {
                $table->enum('relationship_type', ['casual', 'long-term', 'marriage', 'friendship', 'other'])->nullable();
            });
            DB::statement("UPDATE users SET relationship_type = relationship_type_temp");
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('relationship_type_temp');
            });
        } else {
            // For MySQL: Convert VARCHAR back to ENUM with new values
            DB::statement("ALTER TABLE users MODIFY relationship_type ENUM('casual', 'long-term', 'marriage', 'friendship', 'other') NULL");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Step 1: Convert enum to string first
        if (DB::getDriverName() === 'sqlite') {
            Schema::table('users', function (Blueprint $table) {
                $table->string('relationship_type_temp')->nullable();
            });
            DB::statement("UPDATE users SET relationship_type_temp = relationship_type");
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('relationship_type');
            });
            Schema::table('users', function (Blueprint $table) {
                $table->string('relationship_type')->nullable();
            });
            DB::statement("UPDATE users SET relationship_type = relationship_type_temp");
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('relationship_type_temp');
            });
        } else {
            DB::statement("ALTER TABLE users MODIFY relationship_type VARCHAR(50) NULL");
        }

        // Step 2: Map new values back to old values
        DB::statement("UPDATE users SET relationship_type = 'serious-relationship' WHERE relationship_type = 'long-term'");
        DB::statement("UPDATE users SET relationship_type = 'friends' WHERE relationship_type = 'friendship'");

        // Step 3: Convert back to old enum
        if (DB::getDriverName() === 'sqlite') {
            Schema::table('users', function (Blueprint $table) {
                $table->string('relationship_type_temp')->nullable();
            });
            DB::statement("UPDATE users SET relationship_type_temp = relationship_type");
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('relationship_type');
            });
            Schema::table('users', function (Blueprint $table) {
                $table->enum('relationship_type', ['casual', 'serious-relationship', 'marriage', 'friends'])->nullable();
            });
            DB::statement("UPDATE users SET relationship_type = relationship_type_temp");
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('relationship_type_temp');
            });
        } else {
            DB::statement("ALTER TABLE users MODIFY relationship_type ENUM('casual', 'serious-relationship', 'marriage', 'friends') NULL");
        }
    }
};
