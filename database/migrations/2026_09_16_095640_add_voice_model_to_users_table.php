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
        Schema::table('users', function (Blueprint $table) {
            // Backbone speaker-verification yang dipakai user saat enroll
            // (ecapa / resnet34 / wavlm). Embedding antar model tidak
            // comparable, jadi verifikasi login WAJIB pakai model yang sama
            // dengan yang tersimpan di sini -- bukan pilihan bebas user.
            $table->string('voice_model')->nullable()->after('voice_enrolled_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('voice_model');
        });
    }
};
