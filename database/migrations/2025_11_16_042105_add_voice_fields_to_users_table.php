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
            // Path ke file audio suara user
            $table->string('voice_path')->nullable()->after('password');
            
            // Embedding/fingerprint suara dalam format JSON
            $table->text('voice_embedding')->nullable()->after('voice_path');
            
            // Timestamp enrollment suara
            $table->timestamp('voice_enrolled_at')->nullable()->after('voice_embedding');
            $table->string('phone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            //
        });
    }
};
