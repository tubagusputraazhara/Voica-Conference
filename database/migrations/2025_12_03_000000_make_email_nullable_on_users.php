<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Note: changing column nullability requires the doctrine/dbal package.
        // If you get an error about the `change()` method, run:
        // composer require doctrine/dbal
        Schema::table('users', function (Blueprint $table) {
            // Drop unique index on email if it exists
            try {
                $table->dropUnique(['email']);
            } catch (\Throwable $e) {
                // ignore if index does not exist
            }

            // Make email nullable
            try {
                $table->string('email')->nullable()->change();
            } catch (\Throwable $e) {
                // If change() is not supported, swallow and let user run composer require doctrine/dbal
                Log::warning('Could not change `email` column to nullable: ' . $e->getMessage());
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            try {
                $table->string('email')->nullable(false)->change();
                $table->unique('email');
            } catch (\Throwable $e) {
                Log::warning('Could not revert `email` column changes: ' . $e->getMessage());
            }
        });
    }
};
