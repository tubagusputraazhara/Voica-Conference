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
        Schema::create('voice_verification_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');

            // Verification info
            $table->string('action')->default('voice_verification'); // login, voice_lock, etc
            $table->boolean('success')->default(false);
            $table->boolean('is_match')->default(false);

            // AASIST scores
            $table->decimal('aasist_bonafide', 5, 2)->nullable(); // 0.00 - 100.00

            // ECAPA-TDNN scores
            $table->decimal('ecapa_similarity', 5, 2)->nullable(); // 0.00 - 100.00
            $table->decimal('ecapa_threshold', 5, 2)->nullable();  // 0.00 - 100.00

            // Rejection info
            $table->string('rejected_reason')->nullable(); // spoof_detected, wrong_text, voice_mismatch

            // Context
            $table->decimal('transaction_amount', 15, 2)->nullable();
            $table->string('ip_address', 45)->nullable();

            $table->timestamps();

            // Indexes for analytics
            $table->index(['user_id', 'created_at']);
            $table->index(['success', 'created_at']);
            $table->index('rejected_reason');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('voice_verification_logs');
    }
};
