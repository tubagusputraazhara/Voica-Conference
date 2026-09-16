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
        // Check if user_id column doesn't exist
        if (!Schema::hasColumn('budget', 'user_id')) {
            Schema::table('budget', function (Blueprint $table) {
                $table->unsignedBigInteger('user_id')->nullable()->after('id');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            });
            
            // Update existing records
            $firstUserId = DB::table('users')->first()->id ?? 1;
            DB::statement("UPDATE budget SET user_id = {$firstUserId} WHERE user_id IS NULL");
            
            Schema::table('budget', function (Blueprint $table) {
                $table->unsignedBigInteger('user_id')->nullable(false)->change();
            });
        }
        
        // Add icon column
        if (!Schema::hasColumn('budget', 'icon')) {
            Schema::table('budget', function (Blueprint $table) {
                $table->string('icon')->default('💰')->after('kategori');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('budget', function (Blueprint $table) {
            if (Schema::hasColumn('budget', 'icon')) {
                $table->dropColumn('icon');
            }
            // Don't drop user_id as it might be used elsewhere
        });
    }
};
