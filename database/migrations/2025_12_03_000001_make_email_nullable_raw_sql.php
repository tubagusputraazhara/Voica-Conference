<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = DB::getDriverName();

        try {
            if ($driver === 'mysql') {
                // Drop unique index if exists (naming may vary).
                // Try common index names used by Laravel.
                DB::statement('ALTER TABLE `users` DROP INDEX `users_email_unique`');
            } elseif ($driver === 'sqlite') {
                // SQLite requires table rebuild; skip automatic change.
                Log::warning('Skipping raw email alteration for SQLite; please alter schema manually');
                return;
            } else {
                // Other drivers: attempt to drop index with common name
                DB::statement('ALTER TABLE users DROP INDEX users_email_unique');
            }
        } catch (\Throwable $e) {
            // ignore if index does not exist
            Log::info('Could not drop users.email unique index: ' . $e->getMessage());
        }

        try {
            if ($driver === 'mysql') {
                // Modify column to nullable
                DB::statement('ALTER TABLE `users` MODIFY `email` VARCHAR(255) NULL');
            } else {
                Log::warning('Email nullable migration not run for driver: ' . $driver);
            }
        } catch (\Throwable $e) {
            Log::error('Failed to alter email column: ' . $e->getMessage());
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::getDriverName();

        try {
            if ($driver === 'mysql') {
                DB::statement('ALTER TABLE `users` MODIFY `email` VARCHAR(255) NOT NULL');
                DB::statement('ALTER TABLE `users` ADD UNIQUE `users_email_unique` (`email`)');
            } else {
                Log::warning('Email revert not run for driver: ' . $driver);
            }
        } catch (\Throwable $e) {
            Log::error('Failed to revert email column: ' . $e->getMessage());
        }
    }
};
