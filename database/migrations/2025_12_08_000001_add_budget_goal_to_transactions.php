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
        Schema::table('transaction', function (Blueprint $table) {
            // Add budget_id and goal_id columns
            $table->unsignedBigInteger('budget_id')->nullable()->after('kategori');
            $table->unsignedBigInteger('goal_id')->nullable()->after('budget_id');
            
            // Add foreign key constraints (optional, for data integrity)
            $table->foreign('budget_id')->references('id')->on('budget')->onDelete('set null');
            $table->foreign('goal_id')->references('id')->on('goals')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transaction', function (Blueprint $table) {
            // Drop foreign keys first
            $table->dropForeign(['budget_id']);
            $table->dropForeign(['goal_id']);
            
            // Drop columns
            $table->dropColumn(['budget_id', 'goal_id']);
        });
    }
};
